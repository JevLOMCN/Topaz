<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Wt2Html;

use Wikimedia\Assert\Assert;
use Wikimedia\Parsoid\Config\Env;
use Wikimedia\Parsoid\Core\InternalException;
use Wikimedia\Parsoid\DOM\Document;
use Wikimedia\Parsoid\Utils\PHPUtils;
use Wikimedia\Parsoid\Wt2Html\TreeBuilder\TreeBuilderStage;
use Wikimedia\Parsoid\Wt2Html\TT\AttributeExpander;
use Wikimedia\Parsoid\Wt2Html\TT\BehaviorSwitchHandler;
use Wikimedia\Parsoid\Wt2Html\TT\DOMFragmentBuilder;
use Wikimedia\Parsoid\Wt2Html\TT\ExtensionHandler;
use Wikimedia\Parsoid\Wt2Html\TT\ExternalLinkHandler;
use Wikimedia\Parsoid\Wt2Html\TT\IncludeOnly;
use Wikimedia\Parsoid\Wt2Html\TT\LanguageVariantHandler;
use Wikimedia\Parsoid\Wt2Html\TT\ListHandler;
use Wikimedia\Parsoid\Wt2Html\TT\NoInclude;
use Wikimedia\Parsoid\Wt2Html\TT\OnlyInclude;
use Wikimedia\Parsoid\Wt2Html\TT\ParagraphWrapper;
use Wikimedia\Parsoid\Wt2Html\TT\PreHandler;
use Wikimedia\Parsoid\Wt2Html\TT\QuoteTransformer;
use Wikimedia\Parsoid\Wt2Html\TT\SanitizerHandler;
use Wikimedia\Parsoid\Wt2Html\TT\TemplateHandler;
use Wikimedia\Parsoid\Wt2Html\TT\TokenStreamPatcher;
use Wikimedia\Parsoid\Wt2Html\TT\WikiLinkHandler;

/**
 * This class assembles parser pipelines from parser stages
 */
class ParserPipelineFactory {
	private static $globalPipelineId = 0;

	private static $stages = [
		"Tokenizer" => [
			"class" => PegTokenizer::class,
		],
		"TokenTransform2" => [
			"class" => TokenTransformManager::class,
			"transformers" => [
				OnlyInclude::class,
				IncludeOnly::class,
				NoInclude::class,

				TemplateHandler::class,
				ExtensionHandler::class,

				// Expand attributes after templates to avoid expanding unused branches
				// No expansion of quotes, paragraphs etc in attributes, as in
				// PHP parser- up to text/x-mediawiki/expanded only.
				AttributeExpander::class,

				// now all attributes expanded to tokens or string
				// more convenient after attribute expansion
				WikiLinkHandler::class,
				ExternalLinkHandler::class,
				LanguageVariantHandler::class,

				// This converts dom-fragment-token tokens all the way to DOM
				// and wraps them in DOMFragment wrapper tokens which will then
				// get unpacked into the DOM by a dom-fragment unpacker.
				DOMFragmentBuilder::class
			],
		],
		"TokenTransform3" => [
			"class" => TokenTransformManager::class,
			"transformers" => [
				TokenStreamPatcher::class,
				// add <pre>s
				PreHandler::class,
				QuoteTransformer::class,
				// add before transforms that depend on behavior switches
				// examples: toc generation, edit sections
				BehaviorSwitchHandler::class,

				ListHandler::class,
				SanitizerHandler::class,
				// Wrap tokens into paragraphs post-sanitization so that
				// tags that converted to text by the sanitizer have a chance
				// of getting wrapped into paragraphs.  The sanitizer does not
				// require the existence of p-tags for its functioning.
				ParagraphWrapper::class
			],
		],
		"TreeBuilder" => [
			// Build a tree out of the fully processed token stream
			"class" => TreeBuilderStage::class,
		],
		"DOMPP" => [
			// Generic DOM transformer.
			// This performs a lot of post-processing of the DOM
			// (Template wrapping, broken wikitext/html detection, etc.)
			"class" => DOMPostProcessor::class,
			"processors" => [],
		],
	];

	private static $pipelineRecipes = [
		// This pipeline takes wikitext as input and emits a fully
		// processed DOM as output. This is the pipeline used for
		// all top-level documents.
		// Stages 1-5 of the pipeline
		"text/x-mediawiki/full" => [
			"outType" => "DOM",
			"stages" => [
				"Tokenizer", "TokenTransform2", "TokenTransform3", "TreeBuilder", "DOMPP"
			]
		],

		// This pipeline takes wikitext as input and emits tokens that
		// have had all templates, extensions, links, images processed
		// Stages 1-2 of the pipeline
		"text/x-mediawiki" => [
			"outType" => "Tokens",
			"stages" => [ "Tokenizer", "TokenTransform2" ]
		],

		// This pipeline takes tokens from the PEG tokenizer and emits
		// tokens that have had all templates and extensions processed.
		// Stage 2 of the pipeline
		"tokens/x-mediawiki" => [
			"outType" => "Tokens",
			"stages" => [ "TokenTransform2" ]
		],

		// This pipeline takes tokens from stage 2 and emits a fully
		// processed DOM as output.
		// Stages 3-5 of the pipeline
		"tokens/x-mediawiki/expanded" => [
			"outType" => "DOM",
			"stages" => [ "TokenTransform3", "TreeBuilder", "DOMPP" ]
		],
	];

	private static $supportedOptions = [
		// If true, templates found in content will have its contents expanded
		'expandTemplates',

		// If true, indicates pipeline is processing the expanded content of a
		// template or its arguments
		'inTemplate',

		// If true, indicates that we are in a <includeonly> context
		// (in current usage, isInclude === inTemplate)
		'isInclude',

		// The extension tag that is being processed (Ex: ref, references)
		// (in current usage, only used for native tag implementation)
		'extTag',

		// Extension-specific options
		'extTagOpts',

		// Content being parsed is used in an inline context
		'inlineContext',

		// Are we processing content of attributes?
		// (in current usage, used for transcluded attr. keys/values)
		'attrExpansion'
	];

	private array $pipelineCache = [];

	/** @var Env */
	private $env;

	public function __construct( Env $env ) {
		$this->env = $env;
	}

	/**
	 * Default options processing
	 *
	 * @param array $options
	 * @return array
	 */
	private function defaultOptions( array $options ): array {
		// default: not in a template
		$options['inTemplate'] ??= false;

		// default: not an include context
		$options['isInclude'] ??= false;

		// default: wrap templates
		$options['expandTemplates'] ??= true;

		// Catch pipeline option typos
		foreach ( $options as $k => $v ) {
			Assert::invariant(
				in_array( $k, self::$supportedOptions, true ),
				'Invalid cacheKey option: ' . $k
			);
		}

		return $options;
	}

	/**
	 * Generic pipeline creation from the above recipes.
	 *
	 * @param string $type
	 * @param string $cacheKey
	 * @param array $options
	 * @return ParserPipeline
	 */
	private function makePipeline(
		string $type, string $cacheKey, array $options
	): ParserPipeline {
		if ( !isset( self::$pipelineRecipes[$type] ) ) {
			throw new InternalException( 'Unsupported Pipeline: ' . $type );
		}
		$recipe = self::$pipelineRecipes[$type];
		$pipeStages = [];
		$prevStage = null;
		$recipeStages = $recipe["stages"];

		foreach ( $recipeStages as $stageId ) {
			$stageData = self::$stages[$stageId];
			$stage = new $stageData["class"]( $this->env, $options, $stageId, $prevStage );
			if ( isset( $stageData["transformers"] ) ) {
				foreach ( $stageData["transformers"] as $tName ) {
					$stage->addTransformer( new $tName( $stage, $options ) );
				}
			} elseif ( isset( $stageData["processors"] ) ) {
				$stage->registerProcessors( $stageData["processors"] );
			}

			$prevStage = $stage;
			$pipeStages[] = $stage;
		}

		return new ParserPipeline(
			$type,
			$recipe["outType"],
			$cacheKey,
			$pipeStages,
			$this->env
		);
	}

	private function getCacheKey( string $cacheKey, array $options ): string {
		if ( empty( $options['isInclude'] ) ) {
			$cacheKey .= '::noInclude';
		}
		if ( empty( $options['expandTemplates'] ) ) {
			$cacheKey .= '::noExpand';
		}
		if ( !empty( $options['inlineContext'] ) ) {
			$cacheKey .= '::inlineContext';
		}
		if ( !empty( $options['inTemplate'] ) ) {
			$cacheKey .= '::inTemplate';
		}
		if ( !empty( $options['attrExpansion'] ) ) {
			$cacheKey .= '::attrExpansion';
		}
		if ( isset( $options['extTag'] ) ) {
			$cacheKey .= '::' . $options['extTag'];
			// FIXME: This is not the best strategy. But, instead of
			// premature complexity, let us see how extensions want to
			// use this and then figure out what constraints are needed.
			if ( isset( $options['extTagOpts'] ) ) {
				$cacheKey .= '::' . PHPUtils::jsonEncode( $options['extTagOpts'] );
			}
		}
		return $cacheKey;
	}

	public function parse( string $src ): Document {
		$pipe = $this->getPipeline( 'text/x-mediawiki/full' );
		$pipe->init( [
			'toplevel' => true,
			'frame' => $this->env->topFrame,
		] );

		$result = $pipe->parseChunkily( $src, [
			'atTopLevel' => true,
			// Top-level doc parsing always start in SOL state
			'sol' => true,
		] );

		return $result->ownerDocument;
	}

	/**
	 * Get a pipeline of a given type.  Pipelines are cached as they are
	 * frequently created.
	 *
	 * @param string $type
	 * @param array $options These also determine the key under which the
	 *   pipeline is cached for reuse.
	 * @return ParserPipeline
	 */
	public function getPipeline(
		string $type, array $options = []
	): ParserPipeline {
		$options = $this->defaultOptions( $options );
		$cacheKey = $this->getCacheKey( $type, $options );

		$this->pipelineCache[$cacheKey] ??= [];

		if ( $this->pipelineCache[$cacheKey] ) {
			$pipe = array_pop( $this->pipelineCache[$cacheKey] );
		} else {
			$pipe = $this->makePipeline( $type, $cacheKey, $options );
		}

		// Debugging aid: Assign unique id to the pipeline
		$pipe->setPipelineId( self::$globalPipelineId++ );

		return $pipe;
	}

	/**
	 * Callback called by a pipeline at the end of its processing. Returns the
	 * pipeline to the cache.
	 *
	 * @param ParserPipeline $pipe
	 */
	public function returnPipeline( ParserPipeline $pipe ): void {
		$cacheKey = $pipe->getCacheKey();
		$this->pipelineCache[$cacheKey] ??= [];
		if ( count( $this->pipelineCache[$cacheKey] ) < 100 ) {
			$this->pipelineCache[$cacheKey][] = $pipe;
		}
	}
}

<?php
declare( strict_types = 1 );

/**
 * Tokenizer for wikitext, using WikiPEG and a
 * separate PEG grammar file
 * (Grammar.pegphp)
 *
 * Use along with a {@link Wt2Html/TreeBuilder/TreeBuilderStage} and the
 * {@link DOMPostProcessor}(s) for HTML output.
 */

namespace Wikimedia\Parsoid\Wt2Html;

use Generator;
use Wikimedia\Assert\Assert;
use Wikimedia\Parsoid\Config\Env;
use Wikimedia\Parsoid\Tokens\EOFTk;
use Wikimedia\Parsoid\Tokens\SourceRange;
use Wikimedia\Parsoid\Utils\PHPUtils;
use Wikimedia\WikiPEG\SyntaxError;

class PegTokenizer extends PipelineStage {
	private $options;
	private $offsets;

	/** @var SyntaxError|null */
	private $lastError;

	/** @var Grammar */
	private $grammar;

	public function __construct(
		Env $env, array $options = [], string $stageId = "",
		?PipelineStage $prevStage = null
	) {
		parent::__construct( $env, $prevStage );
		$this->env = $env;
		$this->options = $options;
		$this->offsets = [];
	}

	private function initGrammar() {
		if ( !$this->grammar ) {
			$this->grammar = new Grammar;
		}
	}

	/**
	 * Get the constructor options.
	 *
	 * @internal
	 * @return array
	 */
	public function getOptions(): array {
		return $this->options;
	}

	/**
	 * Set start and end offsets of the source that generated this DOM.
	 *
	 * @param SourceRange $so
	 */
	public function setSourceOffsets( SourceRange $so ): void {
		$this->offsets['startOffset'] = $so->start;
		$this->offsets['endOffset'] = $so->end;
	}

	/**
	 * See PipelineStage::process docs as well. This doc block refines
	 * the generic arg types to be specific to this pipeline stage.
	 *
	 * @param string $input wikitext to tokenize
	 * @param ?array $opts
	 * - atTopLevel: (bool) Whether we are processing the top-level document
	 * - sol: (bool) Whether input should be processed in start-of-line context
	 * @return array|false The token array, or false for a syntax error
	 */
	public function process( $input, ?array $opts = null ) {
		Assert::invariant( is_string( $input ), "Input should be a string" );
		PHPUtils::assertValidUTF8( $input ); // Transitional check for PHP port
		return $this->tokenizeSync( $input, $opts ?? [] );
	}

	/**
	 * The text is tokenized in chunks (one per top-level block)
	 * and registered event listeners are called with the chunk
	 * to let it get processed further.
	 *
	 * The main worker. Sets up event emission ('chunk' and 'end' events).
	 * Consumers are supposed to register with PegTokenizer before calling
	 * process().
	 *
	 * @param string $text
	 * @param ?array $opts
	 *   - sol (bool) Whether text should be processed in start-of-line context.
	 * @return Generator
	 */
	public function processChunkily( $text, ?array $opts ): Generator {
		if ( !$this->grammar ) {
			$this->initGrammar();
		}

		Assert::invariant( is_string( $text ), "Input should be a string" );
		PHPUtils::assertValidUTF8( $text ); // Transitional check for PHP port

		// Kick it off!
		$pipelineOffset = $this->offsets['startOffset'] ?? 0;
		$args = [
			'env' => $this->env,
			'pipelineId' => $this->getPipelineId(),
			'pegTokenizer' => $this,
			'pipelineOffset' => $pipelineOffset,
			'sol' => !empty( $opts['sol'] ), // defaults to false
			'stream' => true,
			'startRule' => 'start_async',
		];

		try {
			// Wrap wikipeg's generator with our own generator
			// to catch exceptions and track time usage.
			// @phan-suppress-next-line PhanTypeInvalidYieldFrom
			yield from $this->grammar->parse( $text, $args );
			yield [ new EOFTk() ];
		} catch ( SyntaxError $e ) {
			$this->lastError = $e;
			throw $e;
		}
	}

	/**
	 * Tokenize via a rule passed in as an arg.
	 * The text is tokenized synchronously in one shot.
	 *
	 * @param string $text
	 * @param array $args
	 * - sol: (bool) Whether input should be processed in start-of-line context.
	 * - startRule: (string) which tokenizer rule to tokenize with
	 * @return array|false The token array, or false for a syntax error
	 */
	public function tokenizeSync( string $text, array $args = [] ) {
		if ( !$this->grammar ) {
			$this->initGrammar();
		}
		PHPUtils::assertValidUTF8( $text ); // Transitional check for PHP port
		$args += [
			'pegTokenizer' => $this,
			'pipelineId' => $this->getPipelineId(),
			'pipelineOffset' => $this->offsets['startOffset'] ?? 0,
			'startRule' => 'start',
			'sol' => $args['sol'] ?? true, // defaults to true
			'env' => $this->env
		];

		$start = null;
		$profile = null;
		if ( $this->env->profiling() ) {
			$profile = $this->env->getCurrentProfile();
			$start = microtime( true );
		}

		try {
			$toks = $this->grammar->parse( $text, $args );
		} catch ( SyntaxError $e ) {
			$this->lastError = $e;
			return false;
		}

		if ( $profile ) {
			$profile->bumpTimeUse(
				'PEG', 1000 * ( microtime( true ) - $start ), 'PEG' );
		}
		return $toks;
	}

	/**
	 * Tokenizes a string as a rule
	 *
	 * @param string $text The input text
	 * @param string $rule The rule name
	 * @param bool $sol Start of line flag
	 * @return array|false Array of tokens/strings or false on error
	 */
	public function tokenizeAs( string $text, string $rule, bool $sol ) {
		$args = [
			'startRule' => $rule,
			'sol' => $sol,
			'pipelineOffset' => 0
		];
		return $this->tokenizeSync( $text, $args );
	}

	/**
	 * Tokenize a URL.
	 * @param string $text
	 * @return array|false Array of tokens/strings or false on error
	 */
	public function tokenizeURL( string $text ) {
		return $this->tokenizeAs( $text, 'url', /* sol */true );
	}

	/**
	 * Tokenize table cell attributes.
	 * @param string $text
	 * @param bool $sol
	 * @return array|false Array of tokens/strings or false on error
	 */
	public function tokenizeTableCellAttributes( string $text, bool $sol ) {
		return $this->tokenizeAs( $text, 'row_syntax_table_args', $sol );
	}

	/**
	 * If a tokenize method returned false, this will return a string describing the error,
	 * suitable for use in a log entry. If there has not been any error, returns false.
	 *
	 * @return string|false
	 */
	public function getLastErrorLogMessage() {
		if ( $this->lastError ) {
			return "Tokenizer parse error at input location {$this->lastError->location}: " .
				$this->lastError->getMessage();
		} else {
			return false;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function resetState( array $opts ): void {
		TokenizerUtils::resetAnnotationIncludeRegex();
		parent::resetState( $opts );
	}
}

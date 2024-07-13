<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Wt2Html\TT;

use Wikimedia\Assert\Assert;
use Wikimedia\Parsoid\Tokens\EndTagTk;
use Wikimedia\Parsoid\Tokens\EOFTk;
use Wikimedia\Parsoid\Tokens\SelfclosingTagTk;
use Wikimedia\Parsoid\Tokens\TagTk;
use Wikimedia\Parsoid\Tokens\Token;
use Wikimedia\Parsoid\Utils\PipelineUtils;
use Wikimedia\Parsoid\Utils\TokenUtils;
use Wikimedia\Parsoid\Wt2Html\TokenTransformManager;

class DOMFragmentBuilder extends TokenHandler {
	/**
	 * @param TokenTransformManager $manager manager environment
	 * @param array $options options
	 */
	public function __construct( TokenTransformManager $manager, array $options ) {
		parent::__construct( $manager, $options );
	}

	/**
	 * Can/should content represented in 'toks' be processed in its own DOM scope?
	 * 1. No reason to spin up a new pipeline for plain text
	 * 2. In some cases, if templates need not be nested entirely within the
	 *    boundary of the token, we cannot process the contents in a new scope.
	 * @param array $toks
	 * @param Token $contextTok
	 * @return bool
	 */
	private function subpipelineUnnecessary( array $toks, Token $contextTok ): bool {
		for ( $i = 0, $n = count( $toks );  $i < $n;  $i++ ) {
			$t = $toks[$i];

			// For wikilinks and extlinks, templates should be properly nested
			// in the content section. So, we can process them in sub-pipelines.
			// But, for other context-toks, we back out. FIXME: Can be smarter and
			// detect proper template nesting, but, that can be a later enhancement
			// when dom-scope-tokens are used in other contexts.
			if ( $contextTok && $contextTok->getName() !== 'wikilink' &&
				$contextTok->getName() !== 'extlink' &&
				$t instanceof SelfclosingTagTk &&
				 $t->getName() === 'meta' && TokenUtils::hasTypeOf( $t, 'mw:Transclusion' )
			) {
				return true;
			} elseif ( $t instanceof TagTk || $t instanceof EndTagTk || $t instanceof SelfclosingTagTk ) {
				// Since we encountered a complex token, we'll process this
				// in a subpipeline.
				return false;
			}
		}

		// No complex tokens at all -- no need to spin up a new pipeline
		return true;
	}

	private function buildDOMFragment( Token $scopeToken ): TokenHandlerResult {
		$contentKV = $scopeToken->getAttributeKV( 'content' );
		$content = $contentKV->v;
		if ( is_string( $content ) ||
			$this->subpipelineUnnecessary( $content, $scopeToken->getAttributeV( 'contextTok' ) )
		) {
			// New pipeline not needed. Pass them through
			return new TokenHandlerResult( is_string( $content ) ? [ $content ] : $content );
		} else {
			// Source offsets of content
			$srcOffsets = $contentKV->srcOffsets;

			// Without source offsets for the content, it isn't possible to
			// compute DSR and template wrapping in content. So, users of
			// mw:dom-fragment-token should always set offsets on content
			// that comes from the top-level document.
			Assert::invariant(
				$this->options['inTemplate'] || (bool)$srcOffsets,
				'Processing top-level content without source offsets'
			);

			$pipelineOpts = [
				'inlineContext' => $scopeToken->getAttributeV( 'inlineContext' ) === "1",
				'expandTemplates' => $this->options['expandTemplates'],
				'inTemplate' => $this->options['inTemplate']
			];

			// Append EOF
			$content[] = new EOFTk();

			// Process tokens
			$domFragment = PipelineUtils::processContentInPipeline(
				$this->env,
				$this->manager->getFrame(),
				// Append EOF
				$content,
				[
					'pipelineType' => 'tokens/x-mediawiki/expanded',
					'pipelineOpts' => $pipelineOpts,
					'srcOffsets' => $srcOffsets->value,
					'sol' => true
				]
			);

			$toks = PipelineUtils::tunnelDOMThroughTokens(
				$this->env,
				$scopeToken,
				$domFragment,
				[ "pipelineOpts" => $pipelineOpts ]
			);

			return new TokenHandlerResult( $toks );
		}
	}

	/**
	 * @inheritDoc
	 */
	public function onTag( Token $token ): ?TokenHandlerResult {
		return $token->getName() === 'mw:dom-fragment-token' ?
			$this->buildDOMFragment( $token ) : null;
	}
}

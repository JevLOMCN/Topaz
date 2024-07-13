<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Wt2Html\TT;

use Wikimedia\Parsoid\Tokens\EOFTk;
use Wikimedia\Parsoid\Utils\PHPUtils;
use Wikimedia\Parsoid\Utils\TokenUtils;
use Wikimedia\Parsoid\Wt2Html\TokenTransformManager;

/**
 * Simple noinclude implementation.
 * Strips all tokens in noinclude sections.
 */
class NoInclude extends TokenCollector {
	/**
	 * @param TokenTransformManager $manager manager environment
	 * @param array $options options
	 */
	public function __construct( TokenTransformManager $manager, array $options ) {
		parent::__construct( $manager, $options );
	}

	protected function type(): string {
		return 'tag';
	}

	protected function name(): string {
		return 'noinclude';
	}

	protected function toEnd(): bool {
		return true;    // Match the end-of-input if </noinclude> is missing.
	}

	protected function ackEnd(): bool {
		return true;
	}

	protected function transformation( array $collection ): TokenHandlerResult {
		$start = array_shift( $collection );
		$sc = TokenUtils::getTokenType( $start );

		// A stray end tag.
		if ( $sc === 'EndTagTk' ) {
			$meta = TokenCollector::buildMetaToken( $this->manager, 'mw:Includes/NoInclude',
				true, ( $start->dataParsoid->tsr ?? null ), null );
			return new TokenHandlerResult( [ $meta ] );
		}

		// Handle self-closing tag case specially!
		if ( $sc === 'SelfclosingTagTk' ) {
			return ( $this->options['isInclude'] ) ?
				new TokenHandlerResult( [] ) :
				new TokenHandlerResult( [
					TokenCollector::buildMetaToken(
						$this->manager,
						'mw:Includes/NoInclude',
						false,
						( $start->dataParsoid->tsr ?? null ),
						null )
				] );
		}

		$tokens = [];
		$end = array_pop( $collection );
		$eof = $end instanceof EOFTk;

		if ( empty( $this->options['isInclude'] ) ) {
			// Content is preserved
			// Add meta tags for open and close
			$startTSR = $start->dataParsoid->tsr ?? null;
			$endTSR = $end->dataParsoid->tsr ?? null;
			$tokens[] = TokenCollector::buildMetaToken( $this->manager, 'mw:Includes/NoInclude',
				false, $startTSR, null );

			PHPUtils::pushArray( $tokens, $collection );
			if ( $end && !$eof ) {
				$tokens[] = TokenCollector::buildMetaToken( $this->manager, 'mw:Includes/NoInclude',
					true, $endTSR, null );
			}
		} elseif ( !$this->options['inTemplate'] ) {
			// Content is stripped
			$tokens[] = TokenCollector::buildStrippedMetaToken( $this->manager,
				'mw:Includes/NoInclude', $start, $eof ? null : $end );
		}

		// Preserve EOF
		if ( $eof ) {
			$tokens[] = $end;
		}

		return new TokenHandlerResult( $tokens );
	}
}

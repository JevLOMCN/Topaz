<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Html2Wt\DOMHandlers;

use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\Html2Wt\SerializerState;
use Wikimedia\Parsoid\Utils\DiffDOMUtils;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMDataUtils;

class BRHandler extends DOMHandler {

	public function __construct() {
		parent::__construct( false );
	}

	/** @inheritDoc */
	public function handle(
		Element $node, SerializerState $state, bool $wrapperUnmodified = false
	): ?Node {
		if ( $state->singleLineContext->enforced()
			 || ( DOMDataUtils::getDataParsoid( $node )->stx ?? null ) === 'html'
			 || DOMCompat::nodeName( $node->parentNode ) !== 'p'
		) {
			// <br/> has special newline-based semantics in
			// parser-generated <p><br/>.. HTML
			$state->emitChunk( '<br />', $node );
		}

		// If P_BR (or P_BR_P), dont emit anything for the <br> so that
		// constraints propagate to the next node that emits content.
		return $node->nextSibling;
	}

	/** @inheritDoc */
	public function before( Element $node, Node $otherNode, SerializerState $state ): array {
		if ( $state->singleLineContext->enforced() || !$this->isPbr( $node ) ) {
			return [];
		}

		$c = $state->sep->constraints ?: [ 'min' => 0 ];
		// <h2>..</h2><p><br/>..
		// <p>..</p><p><br/>..
		// In all cases, we need at least 3 newlines before
		// any content that follows the <br/>.
		// Whether we need 4 depends what comes after <br/>.
		// content or a </p>. The after handler deals with it.
		return [ 'min' => max( 3, $c['min'] + 1 ) ];
	}

	/**
	 * @inheritDoc
	 * NOTE: There is an asymmetry in the before/after handlers.
	 */
	public function after( Element $node, Node $otherNode, SerializerState $state ): array {
		// Note that the before handler has already forced 1 additional
		// newline for all <p><br/> scenarios which simplifies the work
		// of the after handler.
		//
		// Nothing changes with constraints if we are not
		// in a P-P transition. <br/> has special newline-based
		// semantics only in a parser-generated <p><br/>.. HTML.

		if ( $state->singleLineContext->enforced()
			 || !PHandler::isPPTransition( DiffDOMUtils::nextNonSepSibling( $node->parentNode ) )
		) {
			return [];
		}

		$c = $state->sep->constraints ?: [ 'min' => 0 ];
		if ( $this->isPbrP( $node ) ) {
			// The <br/> forces an additional newline when part of
			// a <p><br/></p>.
			//
			// Ex: <p><br/></p><p>..</p> => at least 4 newlines before
			// content of the *next* p-tag.
			return [ 'min' => max( 4, $c['min'] + 1 ) ];
		} elseif ( $this->isPbr( $node ) ) {
			// Since the <br/> is followed by content, the newline
			// constraint isn't bumped.
			//
			// Ex: <p><br/>..<p><p>..</p> => at least 2 newlines after
			// content of *this* p-tag
			return [ 'min' => max( 2, $c['min'] ) ];
		}

		return [];
	}

	private function isPbr( Element $br ): bool {
		return ( DOMDataUtils::getDataParsoid( $br )->stx ?? null ) !== 'html'
			&& DOMCompat::nodeName( $br->parentNode ) === 'p'
			&& DiffDOMUtils::firstNonSepChild( $br->parentNode ) === $br;
	}

	private function isPbrP( Element $br ): bool {
		return $this->isPbr( $br ) && DiffDOMUtils::nextNonSepSibling( $br ) === null;
	}

}

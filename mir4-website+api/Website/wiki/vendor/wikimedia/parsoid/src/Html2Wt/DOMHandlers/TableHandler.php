<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Html2Wt\DOMHandlers;

use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\Html2Wt\SerializerState;
use Wikimedia\Parsoid\Utils\DiffDOMUtils;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMDataUtils;
use Wikimedia\Parsoid\Utils\DOMUtils;
use Wikimedia\Parsoid\Utils\WTUtils;

class TableHandler extends DOMHandler {

	public function __construct() {
		parent::__construct( false );
	}

	/** @inheritDoc */
	public function handle(
		Element $node, SerializerState $state, bool $wrapperUnmodified = false
	): ?Node {
		$dp = DOMDataUtils::getDataParsoid( $node );
		$wt = $dp->startTagSrc ?? '{|';
		$indentTable = DOMCompat::nodeName( $node->parentNode ) === 'dd'
			&& DiffDOMUtils::previousNonSepSibling( $node ) === null;
		if ( $indentTable ) {
			$state->singleLineContext->disable();
		}
		$state->emitChunk(
			$this->serializeTableTag( $wt, '', $state, $node, $wrapperUnmodified ),
			$node
		);
		if ( !WTUtils::isLiteralHTMLNode( $node ) ) {
			$state->wikiTableNesting++;
		}
		$state->serializeChildren( $node );
		if ( !WTUtils::isLiteralHTMLNode( $node ) ) {
			$state->wikiTableNesting--;
		}
		if ( $state->sep->constraints === null ) {
			// Special case hack for "{|\n|}" since state.sep is
			// cleared in SSP.emitSep after a separator is emitted.
			// However, for {|\n|}, the <table> tag has no element
			// children which means lastchild -> parent constraint
			// is never computed and set here.
			$state->sep->constraints = [ 'min' => 1, 'max' => 2, 'constraintInfo' => [] ];
		}
		$state->emitChunk( $dp->endTagSrc ?? '|}', $node );
		if ( $indentTable ) {
			$state->singleLineContext->pop();
		}
		return $node->nextSibling;
	}

	/** @inheritDoc */
	public function before( Element $node, Node $otherNode, SerializerState $state ): array {
		// Handle special table indentation case!
		if ( $node->parentNode === $otherNode && DOMCompat::nodeName( $otherNode ) === 'dd' ) {
			return [ 'min' => 0, 'max' => 2 ];
		} else {
			return [ 'min' => 1, 'max' => 2 ];
		}
	}

	/** @inheritDoc */
	public function after( Element $node, Node $otherNode, SerializerState $state ): array {
		if ( ( WTUtils::isNewElt( $node ) || WTUtils::isNewElt( $otherNode ) )
			&& !DOMUtils::atTheTop( $otherNode )
		) {
			return [ 'min' => 1, 'max' => 2 ];
		} else {
			return [ 'min' => 0, 'max' => 2 ];
		}
	}

	/** @inheritDoc */
	public function firstChild( Node $node, Node $otherNode, SerializerState $state ): array {
		return [ 'min' => 1, 'max' => $this->maxNLsInTable( $node, $otherNode ) ];
	}

	/** @inheritDoc */
	public function lastChild( Node $node, Node $otherNode, SerializerState $state ): array {
		return [ 'min' => 1, 'max' => $this->maxNLsInTable( $node, $otherNode ) ];
	}

}

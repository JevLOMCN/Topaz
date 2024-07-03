<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Html2Wt\DOMHandlers;

use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\Html2Wt\SerializerState;
use Wikimedia\Parsoid\NodeData\DataParsoid;
use Wikimedia\Parsoid\Utils\DiffDOMUtils;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMDataUtils;

class TRHandler extends DOMHandler {

	public function __construct() {
		parent::__construct( true );
	}

	/** @inheritDoc */
	public function handle(
		Element $node, SerializerState $state, bool $wrapperUnmodified = false
	): ?Node {
		$dp = DOMDataUtils::getDataParsoid( $node );

		if ( $this->trWikitextNeeded( $node, $dp ) ) {
			$state->emitChunk(
				$this->serializeTableTag(
					$dp->startTagSrc ?? '|-', '', $state,
					$node, $wrapperUnmodified
				),
				$node
			);
		}

		$state->serializeChildren( $node );
		return $node->nextSibling;
	}

	/** @inheritDoc */
	public function before( Element $node, Node $otherNode, SerializerState $state ): array {
		if ( $this->trWikitextNeeded( $node, DOMDataUtils::getDataParsoid( $node ) ) ) {
			return [ 'min' => 1, 'max' => $this->maxNLsInTable( $node, $otherNode ) ];
		} else {
			return [ 'min' => 0, 'max' => $this->maxNLsInTable( $node, $otherNode ) ];
		}
	}

	/** @inheritDoc */
	public function after( Element $node, Node $otherNode, SerializerState $state ): array {
		return [ 'min' => 0, 'max' => $this->maxNLsInTable( $node, $otherNode ) ];
	}

	private function trWikitextNeeded( Element $node, DataParsoid $dp ): bool {
		// If the token has 'startTagSrc' set, it means that the tr
		// was present in the source wikitext and we emit it -- if not,
		// we ignore it.
		// ignore comments and ws
		if ( ( $dp->startTagSrc ?? null ) || DiffDOMUtils::previousNonSepSibling( $node ) ) {
			return true;
		} else {
			// If parent has a thead/tbody previous sibling, then
			// we need the |- separation. But, a caption preceded
			// this node's parent, all is good.
			$parentSibling = DiffDOMUtils::previousNonSepSibling( $node->parentNode );

			// thead/tbody/tfoot is always present around tr tags in the DOM.
			return $parentSibling && DOMCompat::nodeName( $parentSibling ) !== 'caption';
		}
	}

}

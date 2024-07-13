<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Html2Wt\DOMHandlers;

use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\Html2Wt\SerializerState;
use Wikimedia\Parsoid\Utils\WTUtils;

class LinkHandler extends DOMHandler {

	public function __construct() {
		parent::__construct( false );
	}

	/** @inheritDoc */
	public function handle(
		Element $node, SerializerState $state, bool $wrapperUnmodified = false
	): ?Node {
		$state->serializer->linkHandler( $node );
		return $node->nextSibling;
	}

	/** @inheritDoc */
	public function before( Element $node, Node $otherNode, SerializerState $state ): array {
		// sol-transparent link nodes are the only thing on their line.
		// But, don't force separators wrt to its parent (body, p, list, td, etc.)
		if ( $otherNode !== $node->parentNode
			&& WTUtils::isSolTransparentLink( $node ) && !WTUtils::isRedirectLink( $node )
			&& !WTUtils::isEncapsulationWrapper( $node )
		) {
			return [ 'min' => 1 ];
		} else {
			return [];
		}
	}

	/** @inheritDoc */
	public function after( Element $node, Node $otherNode, SerializerState $state ): array {
		// sol-transparent link nodes are the only thing on their line
		// But, don't force separators wrt to its parent (body, p, list, td, etc.)
		if ( $otherNode !== $node->parentNode
			&& WTUtils::isSolTransparentLink( $node ) && !WTUtils::isRedirectLink( $node )
			&& !WTUtils::isEncapsulationWrapper( $node )
		) {
			return [ 'min' => 1 ];
		} else {
			return [];
		}
	}

}

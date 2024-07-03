<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Html2Wt\DOMHandlers;

use Wikimedia\Parsoid\Core\MediaStructure;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\Html2Wt\LinkHandlerUtils;
use Wikimedia\Parsoid\Html2Wt\SerializerState;
use Wikimedia\Parsoid\Utils\DOMUtils;
use Wikimedia\Parsoid\Utils\WTUtils;

class FigureHandler extends DOMHandler {

	public function __construct() {
		parent::__construct( false );
	}

	/** @inheritDoc */
	public function handle(
		Element $node, SerializerState $state, bool $wrapperUnmodified = false
	): ?Node {
		LinkHandlerUtils::figureHandler(
			$state, $node, MediaStructure::parse( $node )
		);
		return $node->nextSibling;
	}

	/** @inheritDoc */
	public function before( Element $node, Node $otherNode, SerializerState $state ): array {
		if ( WTUtils::isNewElt( $node ) && DOMUtils::atTheTop( $node->parentNode ) ) {
			return [ 'min' => 1 ];
		}
		return [];
	}

	/** @inheritDoc */
	public function after( Element $node, Node $otherNode, SerializerState $state ): array {
		if ( WTUtils::isNewElt( $node ) && DOMUtils::atTheTop( $node->parentNode ) ) {
			return [ 'min' => 1 ];
		}
		return [];
	}

}

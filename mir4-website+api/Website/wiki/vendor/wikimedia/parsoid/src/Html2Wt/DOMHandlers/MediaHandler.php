<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Html2Wt\DOMHandlers;

use Wikimedia\Parsoid\Core\MediaStructure;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\Html2Wt\LinkHandlerUtils;
use Wikimedia\Parsoid\Html2Wt\SerializerState;
use Wikimedia\Parsoid\Utils\DOMCompat;

class MediaHandler extends DOMHandler {

	public function __construct() {
		parent::__construct( false );
	}

	/** @inheritDoc */
	public function handle(
		Element $node, SerializerState $state, bool $wrapperUnmodified = false
	): ?Node {
		if ( DOMCompat::nodeName( $node ) === 'figure-inline' ) {
			$ms = MediaStructure::parse( $node );
		} else {
			$ms = new MediaStructure( $node );
		}
		LinkHandlerUtils::figureHandler( $state, $node, $ms );
		return $node->nextSibling;
	}

}

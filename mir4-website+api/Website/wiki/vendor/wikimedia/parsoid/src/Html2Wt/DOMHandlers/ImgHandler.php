<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Html2Wt\DOMHandlers;

use Wikimedia\Parsoid\Core\MediaStructure;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\Html2Wt\LinkHandlerUtils;
use Wikimedia\Parsoid\Html2Wt\SerializerState;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMUtils;

class ImgHandler extends DOMHandler {

	public function __construct() {
		parent::__construct( false );
	}

	/** @inheritDoc */
	public function handle(
		Element $node, SerializerState $state, bool $wrapperUnmodified = false
	): ?Node {
		if ( DOMUtils::hasRel( $node, 'mw:externalImage' ) ) {
			$state->serializer->emitWikitext( DOMCompat::getAttribute( $node, 'src' ) ?? '', $node );
		} else {
			LinkHandlerUtils::figureHandler( $state, $node, new MediaStructure( $node ) );
		}
		return $node->nextSibling;
	}

}

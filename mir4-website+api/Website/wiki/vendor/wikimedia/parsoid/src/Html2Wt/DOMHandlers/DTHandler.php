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

class DTHandler extends DOMHandler {

	public function __construct() {
		parent::__construct( true );
	}

	/** @inheritDoc */
	public function handle(
		Element $node, SerializerState $state, bool $wrapperUnmodified = false
	): ?Node {
		$firstChildElement = DiffDOMUtils::firstNonSepChild( $node );
		if ( !DOMUtils::isList( $firstChildElement )
			 || WTUtils::isLiteralHTMLNode( $firstChildElement )
		) {
			$state->emitChunk( $this->getListBullets( $state, $node ), $node );
		}
		$liHandler = static function ( $state, $text, $opts ) use ( $node ) {
			return $state->serializer->wteHandlers->liHandler( $node, $state, $text, $opts );
		};
		$state->singleLineContext->enforce();
		$state->serializeChildren( $node, $liHandler );
		$state->singleLineContext->pop();
		return $node->nextSibling;
	}

	/** @inheritDoc */
	public function before( Element $node, Node $otherNode, SerializerState $state ): array {
		return [ 'min' => 1, 'max' => 2 ];
	}

	/** @inheritDoc */
	public function after( Element $node, Node $otherNode, SerializerState $state ): array {
		if ( DOMCompat::nodeName( $otherNode ) === 'dd'
			&& $otherNode instanceof Element // for static analyzers
			&& ( DOMDataUtils::getDataParsoid( $otherNode )->stx ?? null ) === 'row'
		) {
			return [ 'min' => 0, 'max' => 0 ];
		} else {
			return $this->wtListEOL( $node, $otherNode );
		}
	}

	/** @inheritDoc */
	public function firstChild( Node $node, Node $otherNode, SerializerState $state ): array {
		if ( !DOMUtils::isList( $otherNode ) ) {
			return [ 'min' => 0, 'max' => 0 ];
		} else {
			return [];
		}
	}

}

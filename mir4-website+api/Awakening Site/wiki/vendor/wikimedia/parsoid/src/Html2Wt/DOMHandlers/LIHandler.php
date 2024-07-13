<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Html2Wt\DOMHandlers;

use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\Html2Wt\DiffUtils;
use Wikimedia\Parsoid\Html2Wt\SerializerState;
use Wikimedia\Parsoid\Utils\DiffDOMUtils;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMDataUtils;
use Wikimedia\Parsoid\Utils\DOMUtils;
use Wikimedia\Parsoid\Utils\WTUtils;

class LIHandler extends DOMHandler {

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

		// Recover trailing whitespace (only on unmodified innermost <li> nodes);
		// Consider "*** foo ". Since WS is only trimmed on the innermost <li> node,
		// it makes sense to recover this only for the innermost <li> node.
		// [ Given current DSR offsets, without this check, we'll recover one space for
		//   every nested <li> node which makes for lotsa dirty diffs. ]
		$lastChild = DiffDOMUtils::lastNonSepChild( $node );
		if ( $lastChild && !DOMUtils::isList( $lastChild ) &&
			!DiffUtils::hasDiffMarkers( $lastChild ) &&
			!( $lastChild instanceof Element && $lastChild->hasAttribute( 'data-mw-selser-wrapper' ) )
		) {
			$trailingSpace = $state->recoverTrimmedWhitespace( $node, false );
			if ( $trailingSpace ) {
				$state->appendSep( $trailingSpace );
			}
		}

		$state->singleLineContext->pop();
		return $node->nextSibling;
	}

	/** @inheritDoc */
	public function before( Element $node, Node $otherNode, SerializerState $state ): array {
		if ( ( $otherNode === $node->parentNode
				&& in_array( DOMCompat::nodeName( $otherNode ), [ 'ul', 'ol' ], true ) )
			|| ( $otherNode instanceof Element
				&& ( DOMDataUtils::getDataParsoid( $otherNode )->stx ?? null ) === 'html' )
		) {
			return [];
		} else {
			return [ 'min' => 1, 'max' => 2 ];
		}
	}

	/** @inheritDoc */
	public function after( Element $node, Node $otherNode, SerializerState $state ): array {
		return $this->wtListEOL( $node, $otherNode );
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

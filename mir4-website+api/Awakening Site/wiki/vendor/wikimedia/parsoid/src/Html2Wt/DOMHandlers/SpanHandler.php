<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Html2Wt\DOMHandlers;

use Wikimedia\Parsoid\Core\MediaStructure;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\DOM\Text;
use Wikimedia\Parsoid\Html2Wt\LinkHandlerUtils;
use Wikimedia\Parsoid\Html2Wt\SerializerState;
use Wikimedia\Parsoid\Html2Wt\WTSUtils;
use Wikimedia\Parsoid\Tokens\KV;
use Wikimedia\Parsoid\Utils\DiffDOMUtils;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMDataUtils;
use Wikimedia\Parsoid\Utils\DOMUtils;
use Wikimedia\Parsoid\Utils\Utils;
use Wikimedia\Parsoid\Utils\WTUtils;

class SpanHandler extends DOMHandler {

	public function __construct() {
		parent::__construct( false );
	}

	/** @inheritDoc */
	public function handle(
		Element $node, SerializerState $state, bool $wrapperUnmodified = false
	): ?Node {
		$env = $state->getEnv();
		$dp = DOMDataUtils::getDataParsoid( $node );
		if ( self::isRecognizedSpanWrapper( $node ) ) {
			if ( DOMUtils::hasTypeOf( $node, 'mw:Nowiki' ) ) {
				$ext = $env->getSiteConfig()->getExtTagImpl( 'nowiki' );
				$src = $ext->domToWikitext( $state->extApi, $node, $wrapperUnmodified );
				$state->singleLineContext->disable();
				$state->serializer->emitWikitext( $src, $node );
				$state->singleLineContext->pop();
			} elseif ( WTUtils::isInlineMedia( $node ) ) {
				LinkHandlerUtils::figureHandler(
					$state, $node, MediaStructure::parse( $node )
				);
			} elseif (
				DOMUtils::hasTypeOf( $node, 'mw:Entity' ) &&
				DiffDOMUtils::hasNChildren( $node, 1 )
			) {
				$contentSrc = ( $node->textContent != '' ) ? $node->textContent
					: DOMCompat::getInnerHTML( $node );
				// handle a new mw:Entity (not handled by selser) by
				// serializing its children
				if ( isset( $dp->src ) && $contentSrc === ( $dp->srcContent ?? null ) ) {
					$state->serializer->emitWikitext( $dp->src, $node );
				} elseif ( $node->firstChild instanceof Text ) {
					$state->emitChunk(
						Utils::entityEncodeAll( $node->firstChild->nodeValue ),
						$node->firstChild );
				} else {
					$state->serializeChildren( $node );
				}
			} elseif ( DOMUtils::hasTypeOf( $node, 'mw:DisplaySpace' ) ) {
				// FIXME(T254501): Throw an UnreachableException here instead
				$state->emitChunk( ' ', $node );
			} elseif ( DOMUtils::matchTypeOf( $node, '#^mw:Placeholder(/|$)#' ) ) {
				if ( isset( $dp->src ) ) {
					$this->emitPlaceholderSrc( $node, $state );
					return $node->nextSibling;
				} else {
					( new FallbackHTMLHandler )->handle( $node, $state );
				}
			}
		} elseif ( $node->hasAttribute( 'data-mw-selser-wrapper' ) ) {
			$state->serializeChildren( $node );
		} else {
			$kvs = array_filter( WTSUtils::getAttributeKVArray( $node ), static function ( KV $kv ) {
				return !preg_match( '/^data-parsoid/', $kv->k )
					&& ( $kv->k !== DOMDataUtils::DATA_OBJECT_ATTR_NAME )
					&& !( $kv->k === 'id' && preg_match( '/^mw[\w-]{2,}$/D', $kv->v ) );
			} );
			if ( !empty( $dp->misnested ) && ( $dp->stx ?? null ) !== 'html'
				&& !count( $kvs )
			) {
				// Discard span wrappers added to flag misnested content.
				// Warn since selser should have reused source.
				$env->log( 'warn', 'Serializing misnested content: ' . DOMCompat::getOuterHTML( $node ) );
				$state->serializeChildren( $node );
			} else {
				// Fall back to plain HTML serialization for spans created
				// by the editor.
				( new FallbackHTMLHandler )->handle( $node, $state );
			}
		}
		return $node->nextSibling;
	}

	private static function isRecognizedSpanWrapper( Element $node ): ?string {
		return DOMUtils::matchTypeOf(
			$node,
			// FIXME(T254501): Remove mw:DisplaySpace
			// TODO: Remove "Image|Video|Audio" when version 2.4.0 of the content
			// is no longer supported
			'#^mw:('
				. 'Nowiki|Entity|DisplaySpace|Placeholder(/\w+)?'
				. '|(File|Image|Video|Audio)(/(Frameless|Frame|Thumb))?'
				. ')$#'
		);
	}

}

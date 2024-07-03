<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Html2Wt\DOMHandlers;

use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\DOM\Text;
use Wikimedia\Parsoid\Html2Wt\DiffUtils;
use Wikimedia\Parsoid\Html2Wt\SerializerState;
use Wikimedia\Parsoid\Html2Wt\WTSUtils;
use Wikimedia\Parsoid\Utils\DiffDOMUtils;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMDataUtils;
use Wikimedia\Parsoid\Utils\DOMUtils;
use Wikimedia\Parsoid\Utils\WTUtils;

class MetaHandler extends DOMHandler {

	public function __construct() {
		parent::__construct( false );
	}

	/** @inheritDoc */
	public function handle(
		Element $node, SerializerState $state, bool $wrapperUnmodified = false
	): ?Node {
		$property = DOMCompat::getAttribute( $node, 'property' ) ?? '';
		$dp = DOMDataUtils::getDataParsoid( $node );
		$dmw = DOMDataUtils::getDataMw( $node );

		if ( isset( $dp->src ) &&
			DOMUtils::matchTypeOf( $node, '#^mw:Placeholder(/|$)#' )
		) {
			$this->emitPlaceholderSrc( $node, $state );
			return $node->nextSibling;
		}

		// Check for property before type so that page properties with
		// templated attrs roundtrip properly.
		// Ex: {{DEFAULTSORT:{{1x|foo}} }}
		if ( $property ) {
			preg_match( '#^mw\:PageProp/(.*)$#D', $property, $switchType );
			if ( $switchType ) {
				$out = $switchType[1];
				$cat = preg_match( '/^(?:category)?(.*)/', $out, $catMatch );
				if ( $cat && (
					// Need this b/c support while RESTBase has Parsoid HTML
					// in storage with meta tags for these.
					// Can be removed as part of T335843
					$catMatch[1] === 'defaultsort' || $catMatch[1] === 'displaytitle'
				) ) {
					$contentInfo = $state->serializer->serializedAttrVal( $node, 'content' );
					if ( WTUtils::hasExpandedAttrsType( $node ) ) {
						$out = '{{' . $contentInfo['value'] . '}}';
					} elseif ( isset( $dp->src ) ) {
						$colon = strpos( $dp->src, ':', 2 );
						$out = preg_replace( '/^([^:}]+).*$/D', "$1", $dp->src, 1 );
						if ( ( $colon === false ) && ( $contentInfo['value'] === '' ) ) {
							$out .= '}}';
						} else {
							$out .= ':' . $contentInfo['value'] . '}}';
						}
					} else {
						$magicWord = mb_strtoupper( $catMatch[1] );
						$out = '{{' . $magicWord . ':' . $contentInfo['value'] . '}}';
					}
				} else {
					$out = $state->getEnv()->getSiteConfig()->getMagicWordWT(
						$switchType[1], $dp->magicSrc ?? '' );
				}
				$state->emitChunk( $out, $node );
			} else {
				( new FallbackHTMLHandler )->handle( $node, $state );
			}
		} elseif ( WTUtils::isAnnotationStartMarkerMeta( $node ) ) {
			$annType = WTUtils::extractAnnotationType( $node );
			if ( $this->needToWriteStartMeta( $state, $node ) ) {
				$datamw = DOMDataUtils::getDataMw( $node );
				$attrs = "";
				if ( isset( $datamw->attrs ) ) {
					foreach ( get_object_vars( $datamw->attrs ) as $k => $v ) {
						if ( $v === "" ) {
							$attrs .= ' ' . $k;
						} else {
							$attrs .= ' ' . $k . '="' . $v . '"';
						}
					}
				}
				// Follow-up on attributes sanitation to happen in T295168
				$state->emitChunk( '<' . $annType . $attrs . '>', $node );
				$state->openAnnotationRange( $annType, $datamw->extendedRange ?? false );
			}
		} elseif ( WTUtils::isAnnotationEndMarkerMeta( $node ) ) {
			if ( $this->needToWriteEndMeta( $state, $node ) ) {
				$annType = WTUtils::extractAnnotationType( $node );
				$state->emitChunk( '</' . $annType . '>', $node );
				$state->closeAnnotationRange( $annType );
			}
		} else {
			switch ( DOMCompat::getAttribute( $node, 'typeof' ) ) {
				case 'mw:Includes/IncludeOnly':
					// Remove the dp.src when older revisions of HTML expire in RESTBase
					$state->emitChunk( $dmw->src ?? $dp->src ?? '', $node );
					break;
				case 'mw:Includes/IncludeOnly/End':
					// Just ignore.
					break;
				case 'mw:Includes/NoInclude':
					$state->emitChunk( $dp->src ?? '<noinclude>', $node );
					break;
				case 'mw:Includes/NoInclude/End':
					$state->emitChunk( $dp->src ?? '</noinclude>', $node );
					break;
				case 'mw:Includes/OnlyInclude':
					$state->emitChunk( $dp->src ?? '<onlyinclude>', $node );
					break;
				case 'mw:Includes/OnlyInclude/End':
					$state->emitChunk( $dp->src ?? '</onlyinclude>', $node );
					break;
				case 'mw:DiffMarker/inserted':
				case 'mw:DiffMarker/deleted':
				case 'mw:DiffMarker/moved':
				case 'mw:Separator':
					// just ignore it
					break;
				default:
					( new FallbackHTMLHandler() )->handle( $node, $state );
			}
		}
		return $node->nextSibling;
	}

	/**
	 * Decides if we need to write an annotation start meta at the place we encounter it
	 * @param SerializerState $state
	 * @param Element $node
	 * @return bool
	 */
	private function needToWriteStartMeta( SerializerState $state, Element $node ): bool {
		if ( !$state->selserMode ) {
			return true;
		}
		if ( WTUtils::isMovedMetaTag( $node ) ) {
			$nextContentSibling = DOMCompat::getNextElementSibling( $node );
			// If the meta tag has been moved, it comes from its next element.... "almost".
			// First exception is if we have several marker annotations in a row - then we need
			// to pass them all. Second exception is if we have fostered content: then we're
			// interested in what happens in the table, which happens _after_ the fostered content.
			while ( $nextContentSibling !== null &&
				( WTUtils::isMarkerAnnotation( $nextContentSibling ) ||
					!empty( DOMDataUtils::getDataParsoid( $nextContentSibling )->fostered )
				)
			) {
				$nextContentSibling = DOMCompat::getNextElementSibling( $nextContentSibling );
			}

			if ( $nextContentSibling !== null ) {
				// When the content from which the meta tag comes gets
				// deleted or modified, we emit _now_ so that we don't risk losing it. The range
				// stays extended in the round-tripped version of the wikitext.
				$nextdiffdata = DOMDataUtils::getDataParsoidDiff( $nextContentSibling );
				if ( DiffUtils::isDiffMarker( $nextContentSibling ) ||
					( $nextdiffdata->diff ?? null ) ) {
					return true;
				}

				return !WTSUtils::origSrcValidInEditedContext( $state, $nextContentSibling );
			}
		}
		return true;
	}

	/**
	 * Decides if we need to write an annotation end meta at the place we encounter it
	 * @param SerializerState $state
	 * @param Element $node
	 * @return bool
	 */
	private function needToWriteEndMeta( SerializerState $state, Element $node ): bool {
		if ( !$state->selserMode ) {
			return true;
		}
		if ( WTUtils::isMovedMetaTag( $node ) ) {
			$prevElementSibling = DOMCompat::getPreviousElementSibling( $node );
			while ( $prevElementSibling !== null &&
				WTUtils::isMarkerAnnotation( $prevElementSibling )
			) {
				$prevElementSibling = DOMCompat::getPreviousElementSibling( $prevElementSibling );
			}
			if ( $prevElementSibling ) {
				$prevdiffdata = DOMDataUtils::getDataParsoidDiff( $prevElementSibling );

				if (
					DiffUtils::isDiffMarker( $prevElementSibling ) ||
					$prevdiffdata !== null && $prevdiffdata->diff !== null
				) {
					return true;
				}
				return !WTSUtils::origSrcValidInEditedContext( $state, $prevElementSibling );
			}
		}
		return true;
	}

	/**
	 * We create a newline (or two) if:
	 *   * the previous element is a block element
	 *   * the previous element is text, AND we're not in an inline-text situation: this
	 *     corresponds to text having been added in VE without creating a paragraph, which happens
	 *     when inserting a new line before the <meta> tag in VE. The "we're not in an inline text"
	 *     is a heuristic and doesn't work for the ends of line for instance, but it shouldn't add
	 *     semantic whitespace either.
	 * @param Node $meta
	 * @param Node $otherNode
	 * @return bool
	 */
	private function needNewLineSepBeforeMeta( Node $meta, Node $otherNode ) {
		return ( $otherNode !== $meta->parentNode
			&& ( $otherNode instanceof Element &&
				DOMUtils::isWikitextBlockNode( $otherNode ) ||
				( $otherNode instanceof Text &&
					DOMUtils::isWikitextBlockNode( DiffDOMUtils::nextNonSepSibling( $meta ) )
				)
			) );
	}

	/** @inheritDoc */
	public function before( Element $node, Node $otherNode, SerializerState $state ): array {
		if ( WTUtils::isAnnotationStartMarkerMeta( $node ) ) {
			if ( $this->needNewLineSepBeforeMeta( $node, $otherNode ) ) {
				return [ 'min' => 2 ];
			} else {
				return [];
			}
		}
		if ( WTUtils::isAnnotationEndMarkerMeta( $node ) ) {
			if ( $this->needNewLineSepBeforeMeta( $node, $otherNode ) ) {
				return [
					'min' => 1
				];
			} else {
				return [];
			}
		}

		$type = DOMCompat::getAttribute( $node, 'typeof' ) ??
			DOMCompat::getAttribute( $node, 'property' );
		if ( $type && str_contains( $type, 'mw:PageProp/categorydefaultsort' ) ) {
			if ( $otherNode instanceof Element
				&& DOMCompat::nodeName( $otherNode ) === 'p'
				&& ( DOMDataUtils::getDataParsoid( $otherNode )->stx ?? null ) !== 'html'
			) {
				// Since defaultsort is outside the p-tag, we need 2 newlines
				// to ensure that it go back into the p-tag when parsed.
				return [ 'min' => 2 ];
			} else {
				return [ 'min' => 1 ];
			}
		} elseif ( WTUtils::isNewElt( $node ) &&
			// Placeholder and annotation metas or <*include*> tags don't need to be serialized on
			// their own line
			!DOMUtils::matchTypeOf( $node, '#^mw:(Placeholder|Includes|Annotation)(/|$)#' )
		) {
			return [ 'min' => 1 ];
		} else {
			return [];
		}
	}

	/** @inheritDoc */
	public function after( Element $node, Node $otherNode, SerializerState $state ): array {
		if ( WTUtils::isAnnotationEndMarkerMeta( $node ) ) {
			if ( $otherNode !== $node->parentNode && $otherNode instanceof Element &&
				DOMUtils::isWikitextBlockNode( $otherNode ) ) {
				return [ 'min' => 2 ];
			} else {
				return [];
			}
		}
		if ( WTUtils::isAnnotationStartMarkerMeta( $node ) ) {
			if ( $otherNode !== $node->parentNode && $otherNode instanceof Element &&
				DOMUtils::isWikitextBlockNode( $otherNode ) ) {
				return [ 'min' => 1 ];
			} else {
				return [];
			}
		}

		// No diffs
		if ( WTUtils::isNewElt( $node ) &&
			// Placeholder and annotation metas or <*include*> tags don't need to be serialized on
			// their own line
			!DOMUtils::matchTypeOf( $node, '#^mw:(Placeholder|Includes|Annotation)(/|$)#' )
		) {
			return [ 'min' => 1 ];
		} else {
			return [];
		}
	}
}

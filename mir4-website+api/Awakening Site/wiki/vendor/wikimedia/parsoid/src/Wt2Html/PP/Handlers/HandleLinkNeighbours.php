<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Wt2Html\PP\Handlers;

use Wikimedia\Parsoid\Config\Env;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Text;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMDataUtils;
use Wikimedia\Parsoid\Utils\DOMUtils;
use Wikimedia\Parsoid\Utils\WTUtils;

class HandleLinkNeighbours {
	/**
	 * Function for fetching the link prefix based on a link node.
	 * The content will be reversed, so be ready for that.
	 *
	 * @param Env $env
	 * @param Element $aNode
	 * @return ?array
	 */
	private static function getLinkPrefix( Env $env, Element $aNode ): ?array {
		$regex = $env->getSiteConfig()->linkPrefixRegex();
		if ( !$regex ) {
			return null;
		}

		$baseAbout = WTUtils::isEncapsulatedDOMForestRoot( $aNode ) ? DOMCompat::getAttribute( $aNode, 'about' ) : null;
		return self::findAndHandleNeighbour( $env, false, $regex, $aNode, $baseAbout );
	}

	/**
	 * Function for fetching the link trail based on a link node.
	 *
	 * @param Env $env
	 * @param Element $aNode
	 * @return ?array
	 */
	private static function getLinkTrail( Env $env, Element $aNode ): ?array {
		$regex = $env->getSiteConfig()->linkTrailRegex();
		if ( !$regex ) {
			return null;
		}

		$baseAbout = WTUtils::isEncapsulatedDOMForestRoot( $aNode ) ? DOMCompat::getAttribute( $aNode, 'about' ) : null;
		return self::findAndHandleNeighbour( $env, true, $regex, $aNode, $baseAbout );
	}

	/**
	 * Abstraction of both link-prefix and link-trail searches.
	 *
	 * @param Env $env
	 * @param bool $goForward
	 * @param string $regex
	 * @param Element $aNode
	 * @param ?string $baseAbout
	 * @return array
	 */
	private static function findAndHandleNeighbour(
		Env $env, bool $goForward, string $regex, Element $aNode, ?string $baseAbout
	): array {
		$nbrs = [];
		$node = $goForward ? $aNode->nextSibling : $aNode->previousSibling;
		while ( $node !== null ) {
			$nextSibling = $goForward ? $node->nextSibling : $node->previousSibling;
			$fromTpl = WTUtils::isEncapsulatedDOMForestRoot( $node );
			$unwrappedSpan = null;
			if ( $node instanceof Element && DOMCompat::nodeName( $node ) === 'span' &&
				!WTUtils::isLiteralHTMLNode( $node ) &&
				// <span> comes from the same template we are in
				$fromTpl && $baseAbout !== null && DOMCompat::getAttribute( $node, 'about' ) === $baseAbout &&
				// Not interested in <span>s wrapping more than 1 node
				( !$node->firstChild || $node->firstChild->nextSibling === null )
			) {
				// With these checks here, we are not going to support link suffixes
				// or link trails coming from a different transclusion than the link itself.
				// {{1x|[[Foo]]}}{{1x|bar}} won't be link-trailed. Similarly for prefixes.
				// But, we want support {{1x|Foo[[bar]]}} style link prefixes where the
				// "Foo" is wrapped in a <span> and carries the transclusion info.
				if ( !$node->hasAttribute( 'typeof' ) ||
					( !$goForward && !$aNode->hasAttribute( 'typeof' ) )
				) {
					$unwrappedSpan = $node;
					$node = $node->firstChild;
				}
			}

			if ( $node instanceof Text && preg_match( $regex, $node->nodeValue, $matches ) && $matches[0] !== '' ) {
				$nbr = [ 'node' => $node, 'src' => $matches[0], 'fromTpl' => $fromTpl ];

				// Link prefix node is templated => migrate transclusion info to $aNode
				if ( $unwrappedSpan && $unwrappedSpan->hasAttribute( 'typeof' ) ) {
					DOMUtils::addTypeOf( $aNode, DOMCompat::getAttribute( $unwrappedSpan, 'typeof' ) ?? '' );
					DOMDataUtils::setDataMw( $aNode, DOMDataUtils::getDataMw( $unwrappedSpan ) );
				}

				if ( $nbr['src'] === $node->nodeValue ) {
					// entire node matches linkprefix/trail
					$node->parentNode->removeChild( $node );
					if ( $unwrappedSpan ) { // The empty span is useless now
						$unwrappedSpan->parentNode->removeChild( $unwrappedSpan );
					}

					// Continue looking at siblings
					$nbrs[] = $nbr;
				} else {
					// part of node matches linkprefix/trail
					$nbr['node'] = $node->ownerDocument->createTextNode( $matches[0] );
					$tn = $node->ownerDocument->createTextNode( preg_replace( $regex, '', $node->nodeValue ) );
					$node->parentNode->replaceChild( $tn, $node );

					// No need to look any further beyond this point
					$nbrs[] = $nbr;
					break;
				}
			} else {
				break;
			}

			$node = $nextSibling;
		}

		return $nbrs;
	}

	/**
	 * Workhorse function for bringing linktrails and link prefixes into link content.
	 * NOTE that this function mutates the node's siblings on either side.
	 *
	 * @param Element $node
	 * @param Env $env
	 * @return bool|Element
	 */
	public static function handler( Element $node, Env $env ) {
		if ( !DOMUtils::matchRel( $node, '#^mw:WikiLink(/Interwiki)?$#D' ) ) {
			return true;
		}

		$firstTplNode = WTUtils::findFirstEncapsulationWrapperNode( $node );
		$inTpl = $firstTplNode !== null && DOMUtils::hasTypeOf( $firstTplNode, 'mw:Transclusion' );

		// Find link prefix neighbors
		$dp = DOMDataUtils::getDataParsoid( $node );
		$prefixNbrs = self::getLinkPrefix( $env, $node );
		if ( !empty( $prefixNbrs ) ) {
			$prefix = '';
			$dataMwCorrection = '';
			$dsrCorrection = 0;
			foreach ( $prefixNbrs as $nbr ) {
				$node->insertBefore( $nbr['node'], $node->firstChild );
				$prefix = $nbr['src'] . $prefix;
				if ( !$nbr['fromTpl'] ) {
					$dataMwCorrection = $nbr['src'] . $dataMwCorrection;
					$dsrCorrection += strlen( $nbr['src'] );
				}
			}

			// Set link prefix
			if ( $prefix !== '' ) {
				$dp->prefix = $prefix;
			}

			// Correct DSR values
			if ( $firstTplNode ) {
				// If this is part of a template, update dsr on that node!
				$dp = DOMDataUtils::getDataParsoid( $firstTplNode );
			}
			if ( $dsrCorrection !== 0 && !empty( $dp->dsr ) ) {
				if ( $dp->dsr->start !== null ) {
					$dp->dsr->start -= $dsrCorrection;
				}
				if ( $dp->dsr->openWidth !== null ) {
					$dp->dsr->openWidth += $dsrCorrection;
				}
			}

			// Update template wrapping data-mw info, if necessary
			if ( $dataMwCorrection !== '' && $inTpl ) {
				$dataMW = DOMDataUtils::getDataMw( $firstTplNode );
				if ( isset( $dataMW->parts ) ) {
					array_unshift( $dataMW->parts, $dataMwCorrection );
				}
			}
		}

		// Find link trail neighbors
		$dp = DOMDataUtils::getDataParsoid( $node );
		$trailNbrs = self::getLinkTrail( $env, $node );
		if ( !empty( $trailNbrs ) ) {
			$trail = '';
			$dataMwCorrection = '';
			$dsrCorrection = 0;
			foreach ( $trailNbrs as $nbr ) {
				$node->appendChild( $nbr['node'] );
				$trail .= $nbr['src'];
				if ( !$nbr['fromTpl'] ) {
					$dataMwCorrection .= $nbr['src'];
					$dsrCorrection += strlen( $nbr['src'] );
				}
			}

			// Set link trail
			if ( $trail !== '' ) {
				$dp->tail = $trail;
			}

			// Correct DSR values
			if ( $firstTplNode ) {
				// If this is part of a template, update dsr on that node!
				$dp = DOMDataUtils::getDataParsoid( $firstTplNode );
			}
			if ( $dsrCorrection !== 0 && !empty( $dp->dsr ) ) {
				if ( $dp->dsr->end !== null ) {
					$dp->dsr->end += $dsrCorrection;
				}
				if ( $dp->dsr->closeWidth !== null ) {
					$dp->dsr->closeWidth += $dsrCorrection;
				}
			}

			// Update template wrapping data-mw info, if necessary
			if ( $dataMwCorrection !== '' && $inTpl ) {
				$dataMW = DOMDataUtils::getDataMw( $firstTplNode );
				if ( isset( $dataMW->parts ) ) {
					$dataMW->parts[] = $dataMwCorrection;
				}
			}

			// If $trailNbs is not empty, $node's tail siblings have been consumed
			return $node;
		}

		return true;
	}
}

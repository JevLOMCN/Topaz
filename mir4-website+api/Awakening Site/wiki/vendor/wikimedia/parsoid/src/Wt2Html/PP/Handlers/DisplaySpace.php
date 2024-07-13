<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Wt2Html\PP\Handlers;

use Wikimedia\Parsoid\Core\DomSourceRange;
use Wikimedia\Parsoid\Core\Sanitizer;
use Wikimedia\Parsoid\DOM\Comment;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\DOM\Text;
use Wikimedia\Parsoid\NodeData\DataParsoid;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMDataUtils;
use Wikimedia\Parsoid\Utils\DOMUtils;
use Wikimedia\Parsoid\Utils\Utils;
use Wikimedia\Parsoid\Utils\WTUtils;

/**
 * Apply french space armoring.
 *
 * See https://www.mediawiki.org/wiki/Specs/HTML#Display_space
 */
class DisplaySpace {

	private static function getTextNodeDSRStart( Text $node ): ?int {
		$parent = $node->parentNode;
		'@phan-var Element $parent';  /** @var Element $parent */
		$dsr = DOMDataUtils::getDataParsoid( $parent )->dsr ?? null;
		if ( !Utils::isValidDSR( $dsr, true ) ) {
			return null;
		}
		$start = $dsr->innerStart();
		$c = $parent->firstChild;
		while ( $c !== $node ) {
			if ( $c instanceof Comment ) {
				$start += WTUtils::decodedCommentLength( $c );
			} elseif ( $c instanceof Text ) {
				$start += strlen( $c->nodeValue );
			} else {
				'@phan-var Element $c';  /** @var Element $c */
				$dsr = DOMDataUtils::getDataParsoid( $c )->dsr ?? null;
				if ( !Utils::isValidDSR( $dsr ) ) {
					return null;
				}
				$start = $dsr->end;
			}
			$c = $c->nextSibling;
		}
		return $start;
	}

	private static function insertDisplaySpace(
		Text $node, int $offset
	): void {
		$str = $node->nodeValue;

		$prefix = substr( $str, 0, $offset );
		$suffix = substr( $str, $offset + 1 );

		$node->nodeValue = $prefix;

		$doc = $node->ownerDocument;
		$post = $doc->createTextNode( $suffix );
		$node->parentNode->insertBefore( $post, $node->nextSibling );

		$start = self::getTextNodeDSRStart( $node );
		if ( $start !== null ) {
			$start += strlen( $prefix );
			$dsr = new DomSourceRange( $start, $start + 1, 0, 0 );
		} else {
			$dsr = new DomSourceRange( null, null, null, null );
		}

		$span = $doc->createElement( 'span' );
		$span->appendChild( $doc->createTextNode( "\u{00A0}" ) );
		$span->setAttribute( 'typeof', 'mw:DisplaySpace' );
		$dp = new DataParsoid;
		$dp->dsr = $dsr;
		DOMDataUtils::setDataParsoid( $span, $dp );
		$node->parentNode->insertBefore( $span, $post );
	}

	/**
	 * Omit handling node
	 *
	 * @param Node $node
	 * @return bool|Node
	 */
	private static function omitNode( Node $node ) {
		$nodeName = DOMCompat::nodeName( $node );

		// Go to next sibling if we encounter pre or raw text elements
		if ( $nodeName === 'pre' || DOMUtils::isRawTextElement( $node ) ) {
			return $node->nextSibling;
		}

		// Run handlers only on text nodes
		if ( !( $node instanceof Text ) ) {
			return true;
		}

		return false;
	}

	/**
	 * French spaces, Guillemet-left
	 *
	 * @param Node $node
	 * @return bool|Element
	 */
	public static function leftHandler( Node $node ) {
		$omit = self::omitNode( $node );
		if ( $omit !== false ) {
			return $omit;
		}

		'@phan-var Text $node'; // @var Text $node

		$key = array_keys( array_slice( Sanitizer::FIXTAGS, 0, 1 ) )[0];
		if ( preg_match( $key, $node->nodeValue, $matches, PREG_OFFSET_CAPTURE ) ) {
			$offset = $matches[0][1];
			self::insertDisplaySpace( $node, $offset );
			return true;
		}
		return true;
	}

	/**
	 * French spaces, Guillemet-right
	 *
	 * @param Node $node
	 * @return bool|Element
	 */
	public static function rightHandler( Node $node ) {
		$omit = self::omitNode( $node );
		if ( $omit !== false ) {
			return $omit;
		}

		'@phan-var Text $node'; // @var Text $node

		$key = array_keys( array_slice( Sanitizer::FIXTAGS, 1, 1 ) )[0];
		if ( preg_match( $key, $node->nodeValue, $matches, PREG_OFFSET_CAPTURE ) ) {
			$offset = $matches[1][1] + strlen( $matches[1][0] );
			self::insertDisplaySpace( $node, $offset );
			return true;
		}
		return true;
	}

}

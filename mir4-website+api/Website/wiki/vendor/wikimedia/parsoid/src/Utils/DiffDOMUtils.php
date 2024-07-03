<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Utils;

use Wikimedia\Parsoid\DOM\Comment;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\DOM\Text;
use Wikimedia\Parsoid\Html2Wt\DiffUtils;

/**
 * Some diff marker aware DOM utils.
 */
class DiffDOMUtils {

	/**
	 * Test the number of children this node has without using
	 * `DOMNode::$childNodes->count()`.  This walks the sibling list and so
	 * takes O(`nchildren`) time -- so `nchildren` is expected to be small
	 * (say: 0, 1, or 2).
	 *
	 * Skips all diff markers by default.
	 * @param Node $node
	 * @param int $nchildren
	 * @param bool $countDiffMarkers
	 * @return bool
	 */
	public static function hasNChildren(
		Node $node, int $nchildren, bool $countDiffMarkers = false
	): bool {
		for ( $child = $node->firstChild; $child; $child = $child->nextSibling ) {
			if ( !$countDiffMarkers && DiffUtils::isDiffMarker( $child ) ) {
				continue;
			}
			if ( $nchildren <= 0 ) {
				return false;
			}
			$nchildren -= 1;
		}
		return ( $nchildren === 0 );
	}

	/**
	 * Is a node a content node?
	 *
	 * @param ?Node $node
	 * @return bool
	 */
	public static function isContentNode( ?Node $node ): bool {
		return !( $node instanceof Comment ) &&
			!DOMUtils::isIEW( $node ) &&
			!DiffUtils::isDiffMarker( $node );
	}

	/**
	 * Get the first child element or non-IEW text node, ignoring
	 * whitespace-only text nodes, comments, and deleted nodes.
	 *
	 * @param Node $node
	 * @return Node|null
	 */
	public static function firstNonSepChild( Node $node ): ?Node {
		$child = $node->firstChild;
		while ( $child && !self::isContentNode( $child ) ) {
			$child = $child->nextSibling;
		}
		return $child;
	}

	/**
	 * Get the last child element or non-IEW text node, ignoring
	 * whitespace-only text nodes, comments, and deleted nodes.
	 *
	 * @param Node $node
	 * @return Node|null
	 */
	public static function lastNonSepChild( Node $node ): ?Node {
		$child = $node->lastChild;
		while ( $child && !self::isContentNode( $child ) ) {
			$child = $child->previousSibling;
		}
		return $child;
	}

	/**
	 * Get the previous non separator sibling node.
	 *
	 * @param Node $node
	 * @return Node|null
	 */
	public static function previousNonSepSibling( Node $node ): ?Node {
		$prev = $node->previousSibling;
		while ( $prev && !self::isContentNode( $prev ) ) {
			$prev = $prev->previousSibling;
		}
		return $prev;
	}

	/**
	 * Get the next non separator sibling node.
	 *
	 * @param Node $node
	 * @return Node|null
	 */
	public static function nextNonSepSibling( Node $node ): ?Node {
		$next = $node->nextSibling;
		while ( $next && !self::isContentNode( $next ) ) {
			$next = $next->nextSibling;
		}
		return $next;
	}

	/**
	 * Return the numbler of non deleted child nodes.
	 *
	 * @param Node $node
	 * @return int
	 */
	public static function numNonDeletedChildNodes( Node $node ): int {
		$n = 0;
		$child = $node->firstChild;
		while ( $child ) {
			if ( !DiffUtils::isDiffMarker( $child ) ) { // FIXME: This is ignoring both inserted/deleted
				$n++;
			}
			$child = $child->nextSibling;
		}
		return $n;
	}

	/**
	 * Get the first non-deleted child of node.
	 *
	 * @param Node $node
	 * @return Node|null
	 */
	public static function firstNonDeletedChild( Node $node ): ?Node {
		$child = $node->firstChild;
		// FIXME: This is ignoring both inserted/deleted
		while ( $child && DiffUtils::isDiffMarker( $child ) ) {
			$child = $child->nextSibling;
		}
		return $child;
	}

	/**
	 * Get the last non-deleted child of node.
	 *
	 * @param Node $node
	 * @return Node|null
	 */
	public static function lastNonDeletedChild( Node $node ): ?Node {
		$child = $node->lastChild;
		// FIXME: This is ignoring both inserted/deleted
		while ( $child && DiffUtils::isDiffMarker( $child ) ) {
			$child = $child->previousSibling;
		}
		return $child;
	}

	/**
	 * Get the next non deleted sibling.
	 *
	 * @param Node $node
	 * @return Node|null
	 */
	public static function nextNonDeletedSibling( Node $node ): ?Node {
		$node = $node->nextSibling;
		while ( $node && DiffUtils::isDiffMarker( $node ) ) { // FIXME: This is ignoring both inserted/deleted
			$node = $node->nextSibling;
		}
		return $node;
	}

	/**
	 * Get the previous non deleted sibling.
	 *
	 * @param Node $node
	 * @return Node|null
	 */
	public static function previousNonDeletedSibling( Node $node ): ?Node {
		$node = $node->previousSibling;
		while ( $node && DiffUtils::isDiffMarker( $node ) ) { // FIXME: This is ignoring both inserted/deleted
			$node = $node->previousSibling;
		}
		return $node;
	}

	/**
	 * Does `node` contain nothing or just non-newline whitespace?
	 * `strict` adds the condition that all whitespace is forbidden.
	 *
	 * @param Node $node
	 * @param bool $strict
	 * @return bool
	 */
	public static function nodeEssentiallyEmpty( Node $node, bool $strict = false ): bool {
		$n = $node->firstChild;
		while ( $n ) {
			if ( $n instanceof Element && !DiffUtils::isDiffMarker( $n ) ) {
				return false;
			} elseif ( $n instanceof Text &&
				( $strict || !preg_match( '/^[ \t]*$/D', $n->nodeValue ) )
			) {
				return false;
			} elseif ( $n instanceof Comment ) {
				return false;
			}
			$n = $n->nextSibling;
		}
		return true;
	}

}

<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Utils;

use Wikimedia\Parsoid\DOM\Node;

/**
 * Non-recursive post-order traversal of a DOM tree.
 */
class DOMPostOrder {
	/**
	 * @suppress PhanEmptyPrivateMethod
	 */
	private function __construct() {
		/* Not meant to be instantiated. */
	}

	/**
	 * Non-recursive post-order traversal of a DOM tree.
	 * @param Node $root
	 * @param callable $visitFunc Called in post-order on each node.
	 */
	public static function traverse( Node $root, callable $visitFunc ): void {
		$node = $root;
		while ( true ) {
			// Find leftmost (grand)child, and visit that first.
			while ( $node->firstChild ) {
				$node = $node->firstChild;
			}
			while ( true ) {
				$visitFunc( $node );
				if ( $node === $root ) {
					return; // Visiting the root is the last thing we do.
				}
				/* Look for right sibling to continue traversal. */
				if ( $node->nextSibling ) {
					$node = $node->nextSibling;
					/* Loop back and visit its leftmost (grand)child first. */
					break;
				}
				/* Visit parent only after we've run out of right siblings. */
				$node = $node->parentNode;
			}
		}
	}
}

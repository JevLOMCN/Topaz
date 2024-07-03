<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Ext;

use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\Utils\DiffDOMUtils as DDU;

/**
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
		return DDU::hasNChildren( $node, $nchildren, $countDiffMarkers );
	}

	/**
	 * Get the first child element or non-IEW text node, ignoring
	 * whitespace-only text nodes, comments, and deleted nodes.
	 *
	 * @param Node $node
	 * @return Node|null
	 */
	public static function firstNonSepChild( Node $node ): ?Node {
		return DDU::firstNonSepChild( $node );
	}

}

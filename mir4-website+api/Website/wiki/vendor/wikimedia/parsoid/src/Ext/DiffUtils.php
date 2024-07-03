<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Ext;

use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\Html2Wt\DiffUtils as DU;

/**
 */
class DiffUtils {

	/**
	 * Check a node to see whether it's a diff marker.
	 */
	public static function isDiffMarker(
		?Node $node, ?string $mark = null
	): bool {
		return DU::isDiffMarker( $node, $mark );
	}

	/**
	 * Check that the diff markers on the node exist.
	 */
	public static function hasDiffMarkers( Node $node ): bool {
		return DU::hasDiffMarkers( $node );
	}

	public static function subtreeUnchanged( Element $node ): bool {
		return DU::subtreeUnchanged( $node );
	}

}

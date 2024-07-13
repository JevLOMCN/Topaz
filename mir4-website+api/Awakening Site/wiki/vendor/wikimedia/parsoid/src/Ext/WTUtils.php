<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Ext;

use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\Utils\WTUtils as WTU;

/**
 * This class provides helpers needed by extensions.
 * These helpers help with extracting wikitext information from the DOM.
 */
class WTUtils {

	/**
	 * Is $node a sealed DOMFragment of a specific extension?
	 */
	public static function isSealedFragmentOfType( Node $node, string $name ): bool {
		return WTU::isSealedFragmentOfType( $node, $name );
	}

	public static function hasVisibleCaption( Element $node ): bool {
		return WTU::hasVisibleCaption( $node );
	}

	public static function textContentFromCaption( Node $node ): string {
		return WTU::textContentFromCaption( $node );
	}

}

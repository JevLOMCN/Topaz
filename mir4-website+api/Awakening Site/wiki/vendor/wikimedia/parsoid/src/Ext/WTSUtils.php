<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Ext;

use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\Html2Wt\WTSUtils as WTSU;

class WTSUtils {

	public static function getShadowInfo(
		Element $node, string $name, ?string $curVal
	): array {
		return WTSU::getShadowInfo( $node, $name, $curVal );
	}

}

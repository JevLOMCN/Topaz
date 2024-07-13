<?php

namespace Cite\Tests;

use Cite\ReferenceStackItem;

/**
 * @license GPL-2.0-or-later
 */
class TestUtils {
	/**
	 * Factory to create internal ref data structures from array fixtures.
	 */
	public static function refFromArray( ?array $val ): ?ReferenceStackItem {
		if ( $val === null ) {
			return null;
		}
		$ref = new ReferenceStackItem();
		foreach ( $val as $k => $v ) {
			$ref->{$k} = $v;
		}
		return $ref;
	}

	/**
	 * @return array<string,array<string|int,?ReferenceStackItem>>
	 */
	public static function refGroupsFromArray( array $refs ) {
		return array_map(
			fn ( $groupRefs ) => array_map( [ self::class, 'refFromArray' ], $groupRefs ),
			$refs
		);
	}
}

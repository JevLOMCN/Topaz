<?php

namespace MediaWiki\Extension\AbuseFilter;

use InvalidArgumentException;

/**
 * @internal
 */
class GlobalNameUtils {
	/** @var string The prefix to use for global filters */
	public const GLOBAL_FILTER_PREFIX = 'global-';

	/**
	 * Given a filter ID and a boolean indicating whether it's global, build a string like
	 * "<GLOBAL_FILTER_PREFIX>$ID". Note that, with global = false, $id is casted to string.
	 * This reverses self::splitGlobalName.
	 *
	 * @param int $id The filter ID
	 * @param bool $global Whether the filter is global
	 * @return string
	 * @todo Calling this method should be avoided wherever possible
	 */
	public static function buildGlobalName( int $id, bool $global = true ): string {
		$prefix = $global ? self::GLOBAL_FILTER_PREFIX : '';
		return "$prefix$id";
	}

	/**
	 * Utility function to split "<GLOBAL_FILTER_PREFIX>$index" to an array [ $id, $global ], where
	 * $id is $index casted to int, and $global is a boolean: true if the filter is global,
	 * false otherwise (i.e. if the $filter === $index). Note that the $index
	 * is always casted to int. Passing anything which isn't an integer-like value or a string
	 * in the shape "<GLOBAL_FILTER_PREFIX>integer" will throw.
	 * This reverses self::buildGlobalName
	 *
	 * @param string|int $filter
	 * @return array
	 * @phan-return array{0:int,1:bool}
	 * @throws InvalidArgumentException
	 */
	public static function splitGlobalName( $filter ): array {
		if ( preg_match( '/^' . self::GLOBAL_FILTER_PREFIX . '\d+$/', $filter ) === 1 ) {
			$id = intval( substr( $filter, strlen( self::GLOBAL_FILTER_PREFIX ) ) );
			return [ $id, true ];
		} elseif ( is_numeric( $filter ) ) {
			return [ (int)$filter, false ];
		} else {
			throw new InvalidArgumentException( "Invalid filter name: $filter" );
		}
	}
}

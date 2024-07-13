<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Utils;

/**
 * This refactors common code in Api and Mock based config computation
 */
class ConfigUtils {
	/**
	 * Compute the interwiki map based on raw data (either manually
	 * configured or obtaianed via an API)
	 *
	 * @param array $iwData
	 * @return array
	 */
	public static function computeInterwikiMap( array $iwData ): array {
		$interwikiMap = [];
		$keys = [
			'prefix' => true,
			'url' => true,
			'protorel' => true,
			'local' => true,
			'localinterwiki' => true,
			'language' => true,
			'extralanglink' => true,
			'linktext' => true,
		];
		$cb = static function ( $v ) {
			return $v !== false;
		};
		foreach ( $iwData as $iwEntry ) {
			$iwEntry['language'] = isset( $iwEntry['language'] );
			// Fix up broken interwiki hrefs that are missing a $1 placeholder
			// Just append the placeholder at the end.
			// This makes sure that the interwikiMatcher adds one match
			// group per URI, and that interwiki links work as expected.

			// Not sure why Phan thinks $iwEntry['url'] is a bool
			// @phan-suppress-next-line PhanTypeMismatchArgumentInternal
			if ( strpos( $iwEntry['url'], '$1' ) === false ) {
				$iwEntry['url'] .= '$1';
			}
			$iwEntry = array_intersect_key( $iwEntry, $keys );
			$interwikiMap[$iwEntry['prefix']] = array_filter( $iwEntry, $cb );
		}

		return $interwikiMap;
	}
}

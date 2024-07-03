<?php

namespace Wikimedia\RemexHtml\DOM;

use Wikimedia\RemexHtml\HTMLData;

class DOMUtils {
	/**
	 * Replace unsupported characters with a code of the form U123456.
	 *
	 * @param string $name
	 * @return string
	 */
	public static function coerceName( $name ) {
		$coercedName =
			mb_encode_numericentity( mb_substr( $name, 0, 1, 'UTF-8' ),
				HTMLData::$nameStartCharConvTable, 'UTF-8', true ) .
			mb_encode_numericentity( mb_substr( $name, 1, null, 'UTF-8' ),
				HTMLData::$nameCharConvTable, 'UTF-8', true );
		$coercedName = preg_replace_callback( '/&#x([0-9A-F]*);/',
			static function ( $m ) {
				return 'U' . str_pad( $m[1], 6, '0', STR_PAD_LEFT );
			},
			$coercedName );
		return $coercedName;
	}

	/**
	 * Invert the encoding produced by coerceName()
	 *
	 * @param string $name
	 * @return string
	 */
	public static function uncoerceName( $name ) {
		return mb_decode_numericentity(
			preg_replace( '/U([0-9A-F]{6})/', '&#x\1;', $name ),
			[ 0, 0x10ffff, 0, 0xffffff ],
			'UTF-8' );
	}
}

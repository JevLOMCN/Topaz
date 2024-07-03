<?php
namespace UtfNormal;

use InvalidArgumentException;

/**
 * Some of these functions are adapted from places in MediaWiki.
 * Should probably merge them for consistency.
 *
 * Copyright © 2004 Brion Vibber <brion@pobox.com>
 * https://www.mediawiki.org/
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @ingroup UtfNormal
 */

class Utils {
	/**
	 * Return UTF-8 sequence for a given Unicode code point.
	 *
	 * @param int $codepoint
	 * @return string
	 * @throws InvalidArgumentException if fed out of range data.
	 */
	public static function codepointToUtf8( $codepoint ) {
		// In PHP 7.2, mb_chr is buggy when $codepoint is 0 (null byte)
		if ( $codepoint === 0 ) {
			return "\u{0000}";
		}
		$char = mb_chr( $codepoint );
		if ( $char === false ) {
			throw new InvalidArgumentException( "Asked for code outside of range ($codepoint)" );
		}

		return $char;
	}

	/**
	 * Take a series of space-separated hexadecimal numbers representing
	 * Unicode code points and return a UTF-8 string composed of those
	 * characters. Used by UTF-8 data generation and testing routines.
	 *
	 * @param string $sequence
	 * @return string
	 * @throws InvalidArgumentException if fed out of range data.
	 * @private Used in tests and data table generation
	 */
	public static function hexSequenceToUtf8( $sequence ) {
		$utf = '';
		foreach ( explode( ' ', $sequence ) as $hex ) {
			$n = hexdec( $hex );
			$utf .= self::codepointToUtf8( $n );
		}

		return $utf;
	}

	/**
	 * Take a UTF-8 string and return a space-separated series of hex
	 * numbers representing Unicode code points. For debugging.
	 *
	 * @param string $str UTF-8 string.
	 * @return string
	 * @private
	 */
	private static function utf8ToHexSequence( $str ) {
		$buf = '';
		foreach ( preg_split( '//u', $str, -1, PREG_SPLIT_NO_EMPTY ) as $cp ) {
			$buf .= sprintf( '%04x ', mb_ord( $cp ) );
		}

		return rtrim( $buf );
	}

	/**
	 * Determine the Unicode codepoint of a single-character UTF-8 sequence.
	 * Does not check for invalid input data.
	 *
	 * @deprecated since 2.1, use mb_ord()
	 *
	 * @param string $char
	 * @return int|false
	 */
	public static function utf8ToCodepoint( $char ) {
		return mb_strlen( $char ) > 1 ? false : mb_ord( $char );
	}

	/**
	 * Escape a string for inclusion in a PHP single-quoted string literal.
	 *
	 * @param string $string String to be escaped.
	 * @return string Escaped string.
	 */
	public static function escapeSingleString( $string ) {
		return strtr(
			$string,
			[
				'\\' => '\\\\',
				'\'' => '\\\''
			]
		);
	}
}

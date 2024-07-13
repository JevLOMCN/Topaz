<?php
/**
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
 */

namespace Wikimedia;

/**
 * Convert an arbitrarily-long string from one numeric base to
 * another, optionally zero-padding to a minimum column width.
 *
 * Supports base 2 through 36; digit values 10-36 are represented
 * as lowercase letters a-z. Input is case-insensitive.
 *
 * @param string $input Input number
 * @param int $sourceBase Base of the input number
 * @param int $destBase Desired base of the output
 * @param int $pad Minimum number of digits in the output (pad with zeroes)
 * @param bool $lowercase Whether to output in lowercase or uppercase
 * @param string $engine Either "gmp", "bcmath", "php" or "auto" (default).
 *  In the case of "auto", the other engines ("gmp" and "bcmath") are used in
 *  the listed order in terms of preference if that PHP extension is actually loaded.
 * @return string|bool The output number as a string, or false on error
 */
function base_convert( $input, $sourceBase, $destBase, $pad = 1,
	$lowercase = true, $engine = 'auto'
) {
	$input = (string)$input;
	if (
		$sourceBase < 2 ||
		$sourceBase > 36 ||
		$destBase < 2 ||
		$destBase > 36 ||
		$sourceBase != (int)$sourceBase ||
		$destBase != (int)$destBase ||
		$pad != (int)$pad ||
		!preg_match(
			"/^[" . substr( '0123456789abcdefghijklmnopqrstuvwxyz', 0, $sourceBase ) . "]+$/i",
			$input
		)
	) {
		return false;
	}

	static $baseChars = [
		10 => 'a', 11 => 'b', 12 => 'c', 13 => 'd', 14 => 'e', 15 => 'f',
		16 => 'g', 17 => 'h', 18 => 'i', 19 => 'j', 20 => 'k', 21 => 'l',
		22 => 'm', 23 => 'n', 24 => 'o', 25 => 'p', 26 => 'q', 27 => 'r',
		28 => 's', 29 => 't', 30 => 'u', 31 => 'v', 32 => 'w', 33 => 'x',
		34 => 'y', 35 => 'z',

		'0' => 0, '1' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 5,
		'6' => 6, '7' => 7, '8' => 8, '9' => 9, 'a' => 10, 'b' => 11,
		'c' => 12, 'd' => 13, 'e' => 14, 'f' => 15, 'g' => 16, 'h' => 17,
		'i' => 18, 'j' => 19, 'k' => 20, 'l' => 21, 'm' => 22, 'n' => 23,
		'o' => 24, 'p' => 25, 'q' => 26, 'r' => 27, 's' => 28, 't' => 29,
		'u' => 30, 'v' => 31, 'w' => 32, 'x' => 33, 'y' => 34, 'z' => 35
	];

	if ( extension_loaded( 'gmp' ) && ( $engine == 'auto' || $engine == 'gmp' ) ) {
		$result = gmp_strval( gmp_init( $input, $sourceBase ), $destBase );
	} elseif ( extension_loaded( 'bcmath' ) && ( $engine == 'auto' || $engine == 'bcmath' ) ) {
		$decimal = '0';
		foreach ( str_split( strtolower( $input ) ) as $char ) {
			$decimal = bcmul( $decimal, (string)$sourceBase );
			$decimal = bcadd( $decimal, $baseChars[$char] );
		}

		for ( $result = ''; bccomp( $decimal, '0' ); $decimal = bcdiv( $decimal, (string)$destBase, 0 ) ) {
			// As of PHP 7.2, bcmod can return a floating point value if bcscale is nonzero
			$result .= $baseChars[(int)bcmod( $decimal, (string)$destBase )];
		}

		$result = strrev( $result );
	} else {
		$inDigits = [];
		foreach ( str_split( strtolower( $input ) ) as $char ) {
			$inDigits[] = $baseChars[$char];
		}

		// Iterate over the input, modulo-ing out an output digit
		// at a time until input is gone.
		$result = '';
		while ( $inDigits ) {
			$work = 0;
			$workDigits = [];

			// Long division...
			foreach ( $inDigits as $digit ) {
				$work *= $sourceBase;
				$work += $digit;

				if ( $workDigits || $work >= $destBase ) {
					$workDigits[] = (int)( $work / $destBase );
				}
				$work %= $destBase;
			}

			// All that division leaves us with a remainder,
			// which is conveniently our next output digit.
			$result .= $baseChars[$work];

			// And we continue!
			$inDigits = $workDigits;
		}

		$result = strrev( $result );
	}

	if ( !$lowercase ) {
		$result = strtoupper( $result );
	}

	return str_pad( $result, $pad, '0', STR_PAD_LEFT );
}

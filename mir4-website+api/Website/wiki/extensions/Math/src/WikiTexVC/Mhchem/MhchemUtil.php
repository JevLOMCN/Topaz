<?php
/**
 * Copyright (c) 2023 Johannes Stegmüller
 *
 * This file is a port of mhchemParser originally authored by Martin Hensel in javascript/typescript.
 * The original license for this software can be found in the accompanying LICENSE.mhchemParser-ts.txt file.
 */

namespace MediaWiki\Extension\Math\WikiTexVC\Mhchem;

/**
 * Some utility classes mostly for creating similar functionalities
 * like in javascript in PHP.
 *
 * concatArray method here has the same functionality as concatArray (~l.194)
 * in mhchemParser.js by Martin Hensel.
 *
 * @author Johannes Stegmüller
 * @license GPL-2.0-or-later
 */
class MhchemUtil {

	/**
	 * The input is used as boolean operator in a javascript-type if condition,
	 * example: "if(input)"
	 * output has the same boolean results as an if-condition in javascript.
	 * arrays as input have to be used like this "issetJS($arr["b"] ?? null);"
	 * properties as input have to be used like this "issetJS($inst->prop ?? null);"
	 * @param mixed|null $input input to be checked in a javascript-type if condition
	 * @return bool indicator if input is populated
	 */
	public static function issetJS( $input ): bool {
		if ( $input === 0 || $input == "" ) {
			return false;
		}
		return true;
	}

	/**
	 * Checks if the incoming string is containing  a regex pattern.
	 * @param string $input string to verify
	 * @param string $subject subject to check, usually empty string
	 * @return bool true if regex pattern, false if not
	 */
	public static function isRegex( string $input, string $subject = "" ): bool {
		/**
		 * Ignoring preg_match phpcs error here, since this is the fastest variant: 582 ms for MMLmhchemTestLocal,
		 * 835 ms for the try catch version of this, 735 ms for the version deactivating error handler
		 * during function call.
		 * See: https://stackoverflow.com/questions/16039362/how-can-i-suppress-phpcs-warnings-using-comments
		 */

        // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
		return !( @preg_match( $input, $subject ) === false );
	}

	/**
	 * Checks if an array is an associative array
	 * @param array $array to check
	 * @return bool true if associative, otherwise false
	 */
	public static function isAssoc( array $array ): bool {
		return ( $array !== array_values( $array ) );
	}

	public static function concatArray( &$a, $b ) {
		if ( self::issetJS( $b ) ) {
			if ( is_array( $b ) && ( self::isAssoc( $b ) ) ) {
				$a[] = $b;
			} elseif ( is_array( $b ) && !self::isAssoc( $b ) ) {
				foreach ( $b as $value ) {
					$a[] = $value;
				}
			} else {
				$a[] = $b;
			}
		}
	}
}

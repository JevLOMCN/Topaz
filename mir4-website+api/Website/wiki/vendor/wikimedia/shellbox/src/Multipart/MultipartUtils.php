<?php

namespace Shellbox\Multipart;

/**
 * Static utility functions for the multipart reader
 */
class MultipartUtils {
	/**
	 * Extract the boundary from a Content-Type string. If the Content-Type is
	 * not multipart, returns false.
	 *
	 * This can be used to get a boundary when constructing a MultipartReader
	 * from a request.
	 *
	 * @param string $contentType
	 * @return bool|string
	 */
	public static function extractBoundary( $contentType ) {
		if ( !preg_match( '!^multipart/[a-z]+ *; *(.*)$!i', $contentType, $m ) ) {
			return false;
		}
		$params = self::decodeParameters( $m[1] );
		return $params['boundary'] ?? false;
	}

	/**
	 * Decode semicolon-separated parameters into an associative array. The
	 * values may be quoted or unquoted. We try to follow the "parameter"
	 * production in RFC 1341.
	 *
	 * @param string $input
	 * @return array
	 * @throws MultipartError
	 */
	public static function decodeParameters( $input ) {
		$params = [];
		$parts = explode( ';', $input );
		foreach ( $parts as $paramString ) {
			$paramParts = explode( '=', $paramString, 2 );
			$paramName = strtolower( trim( $paramParts[0] ) );
			if ( count( $paramParts ) < 2 ) {
				$paramValue = true;
			} else {
				$paramValue = self::decodeTokenOrQuotedString( $paramParts[1] );
			}
			$params[$paramName] = $paramValue;
		}
		return $params;
	}

	/**
	 * Parse the "value" production from RFC 1341, which is either an unquoted
	 * "token" or a quoted string with peculiar backslash escaping.
	 *
	 * @param string $input
	 * @return string
	 * @throws MultipartError
	 */
	public static function decodeTokenOrQuotedString( $input ) {
		$input = trim( $input );
		if ( $input === '' ) {
			return '';
		}
		$tspecials = "()<>@,;:\\\"/[]?=";

		$result = '';
		if ( $input[0] === '"' ) {
			$length = strlen( $input );
			for ( $i = 1; $i < $length; $i++ ) {
				$char = $input[$i];
				if ( $char === '\\' && $i < $length - 1 ) {
					$result .= $input[++$i];
				} elseif ( $char === '"' ) {
					if ( $i !== $length - 1 ) {
						throw new MultipartError( "Invalid quoted string" );
					}
					break;
				} else {
					$result .= $char;
				}
			}
		} else {
			$length = strlen( $input );
			for ( $i = 0; $i < $length; $i++ ) {
				$char = $input[$i];
				$ord = ord( $char );
				if ( strpos( $tspecials, $char ) !== false
					|| $char === ' ' || $char === "\x7f" || $ord < 32
				) {
					throw new MultipartError( "Invalid unquoted string" );
				}
				$result .= $char;
			}
		}
		return $result;
	}
}

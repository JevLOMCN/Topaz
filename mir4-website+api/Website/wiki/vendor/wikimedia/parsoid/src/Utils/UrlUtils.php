<?php

declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Utils;

/**
 * Utilities for manipulating URLs
 * @see https://tools.ietf.org/html/rfc3986
 */
class UrlUtils {

	/**
	 * Parse a possibly-relative URL into components
	 *
	 * Note no percent-decoding is performed, and only minimal syntax validation.
	 *
	 * @param string $url
	 * @return (string|null)[]
	 *  - 'scheme': Scheme of the url, if any.
	 *  - 'authority': Authority part of the url, if any.
	 *    This is the part in between the "//" and the path. For http, this is the "user@host:port".
	 *  - 'path': Path part of the URL. Never null, but may be the empty string.
	 *  - 'query': Query part of the URL, if any.
	 *  - 'fragment': Fragment part of the URL, if any.
	 */
	public static function parseUrl( string $url ): array {
		$ret = [
			'scheme' => null,
			'authority' => null,
			'path' => '',
			'query' => null,
			'fragment' => null,
		];

		// Scheme?
		if ( preg_match( '!^([a-z][a-z0-9+.-]*):!i', $url, $m ) ) {
			$ret['scheme'] = $m[1];
			$url = substr( $url, strlen( $m[0] ) );
		}

		// Fragment?
		$i = strpos( $url, '#' );
		if ( $i !== false ) {
			$ret['fragment'] = substr( $url, $i + 1 );
			$url = substr( $url, 0, $i );
		}

		// Query?
		$i = strpos( $url, '?' );
		if ( $i !== false ) {
			$ret['query'] = substr( $url, $i + 1 );
			$url = substr( $url, 0, $i );
		}

		// Split authority and path
		if ( substr( $url, 0, 2 ) === '//' ) {
			$i = strpos( $url, '/', 2 );
			if ( $i === false ) {
				$ret['authority'] = substr( $url, 2 );
				$ret['path'] = '';
			} else {
				$ret['authority'] = substr( $url, 2, $i - 2 );
				$ret['path'] = substr( $url, $i );
			}
		} else {
			$ret['path'] = $url;
		}

		return $ret;
	}

	/**
	 * This function will reassemble a URL parsed with self::parseURL().
	 *
	 * Note no percent-encoding or syntax validation is performed.
	 *
	 * @param array $urlParts URL parts, as output from self::parseUrl
	 * @return string URL assembled from its component parts
	 */
	public static function assembleUrl( array $urlParts ): string {
		$ret = '';

		if ( isset( $urlParts['scheme'] ) ) {
			$ret .= $urlParts['scheme'] . ':';
		}

		if ( isset( $urlParts['authority'] ) ) {
			$ret .= '//' . $urlParts['authority'];
		}

		if ( isset( $urlParts['path'] ) ) {
			$ret .= $urlParts['path'];
		}

		if ( isset( $urlParts['query'] ) ) {
			$ret .= '?' . $urlParts['query'];
		}

		if ( isset( $urlParts['fragment'] ) ) {
			$ret .= '#' . $urlParts['fragment'];
		}

		return $ret;
	}

	/**
	 * Remove all dot-segments in the provided URL path. For example,
	 * '/a/./b/../c/' becomes '/a/c/'.
	 *
	 * @see https://tools.ietf.org/html/rfc3986#section-5.2.4
	 * @note Copied from MediaWiki's wfRemoveDotSegments
	 * @param string $urlPath URL path, potentially containing dot-segments
	 * @return string URL path with all dot-segments removed
	 */
	public static function removeDotSegments( string $urlPath ): string {
		$output = '';
		$inputOffset = 0;
		$inputLength = strlen( $urlPath );

		while ( $inputOffset < $inputLength ) {
			$prefixLengthOne = substr( $urlPath, $inputOffset, 1 );
			$prefixLengthTwo = substr( $urlPath, $inputOffset, 2 );
			$prefixLengthThree = substr( $urlPath, $inputOffset, 3 );
			$prefixLengthFour = substr( $urlPath, $inputOffset, 4 );
			$trimOutput = false;

			if ( $prefixLengthTwo == './' ) {
				# Step A, remove leading "./"
				$inputOffset += 2;
			} elseif ( $prefixLengthThree == '../' ) {
				# Step A, remove leading "../"
				$inputOffset += 3;
			} elseif ( ( $prefixLengthTwo == '/.' ) && ( $inputOffset + 2 == $inputLength ) ) {
				# Step B, replace leading "/.$" with "/"
				$inputOffset += 1;
				$urlPath[$inputOffset] = '/';
			} elseif ( $prefixLengthThree == '/./' ) {
				# Step B, replace leading "/./" with "/"
				$inputOffset += 2;
			} elseif ( $prefixLengthThree == '/..' && ( $inputOffset + 3 == $inputLength ) ) {
				# Step C, replace leading "/..$" with "/" and
				# remove last path component in output
				$inputOffset += 2;
				$urlPath[$inputOffset] = '/';
				$trimOutput = true;
			} elseif ( $prefixLengthFour == '/../' ) {
				# Step C, replace leading "/../" with "/" and
				# remove last path component in output
				$inputOffset += 3;
				$trimOutput = true;
			} elseif ( ( $prefixLengthOne == '.' ) && ( $inputOffset + 1 == $inputLength ) ) {
				# Step D, remove "^.$"
				$inputOffset += 1;
			} elseif ( ( $prefixLengthTwo == '..' ) && ( $inputOffset + 2 == $inputLength ) ) {
				# Step D, remove "^..$"
				$inputOffset += 2;
			} else {
				# Step E, move leading path segment to output
				if ( $prefixLengthOne == '/' ) {
					$slashPos = strpos( $urlPath, '/', $inputOffset + 1 );
				} else {
					$slashPos = strpos( $urlPath, '/', $inputOffset );
				}
				if ( $slashPos === false ) {
					$output .= substr( $urlPath, $inputOffset );
					$inputOffset = $inputLength;
				} else {
					$output .= substr( $urlPath, $inputOffset, $slashPos - $inputOffset );
					$inputOffset += $slashPos - $inputOffset;
				}
			}

			if ( $trimOutput ) {
				$slashPos = strrpos( $output, '/' );
				if ( $slashPos === false ) {
					$output = '';
				} else {
					$output = substr( $output, 0, $slashPos );
				}
			}
		}

		return $output;
	}

	/**
	 * Expand a relative URL using a base URL
	 *
	 * @see https://tools.ietf.org/html/rfc3986#section-5.2.2
	 * @param string $url Relative URL to expand
	 * @param string $base Base URL to expand relative to
	 * @return string Expanded URL
	 */
	public static function expandUrl( string $url, string $base ): string {
		$b = self::parseUrl( $base );
		$r = self::parseUrl( $url );

		$t = [];
		if ( isset( $r['scheme'] ) ) {
			$t['scheme'] = $r['scheme'];
			$t['authority'] = $r['authority'] ?? null;
			$t['path'] = self::removeDotSegments( $r['path'] );
			$t['query'] = $r['query'] ?? null;
		} else {
			if ( isset( $r['authority'] ) ) {
				$t['authority'] = $r['authority'];
				$t['path'] = self::removeDotSegments( $r['path'] );
				$t['query'] = $r['query'] ?? null;
			} else {
				if ( $r['path'] === '' ) {
					$t['path'] = $b['path'];
					$t['query'] = $r['query'] ?? $b['query'] ?? null;
				} else {
					if ( $r['path'][0] === '/' ) {
						$t['path'] = self::removeDotSegments( $r['path'] );
					} else {
						// start merge(), see RFC 3986 §5.2.3
						if ( isset( $b['authority'] ) && $b['path'] === '' ) {
							$t['path'] = '/' . $r['path'];
						} else {
							$i = strrpos( $b['path'], '/' );
							if ( $i === false ) {
								$t['path'] = $r['path'];
							} else {
								$t['path'] = substr( $b['path'], 0, $i + 1 ) . $r['path'];
							}
						}
						// end merge()
						$t['path'] = self::removeDotSegments( $t['path'] );
					}
					$t['query'] = $r['query'] ?? null;
				}
				$t['authority'] = $b['authority'] ?? null;
			}
			$t['scheme'] = $b['scheme'] ?? null;
		}
		$t['fragment'] = $r['fragment'] ?? null;

		return self::assembleUrl( $t );
	}

	/**
	 * Check whether a given URL has a domain that occurs in a given set of domains
	 *
	 * @param string $url
	 * @param array $domains Array of domains (strings)
	 * @return bool True if the host part of $url ends in one of the strings in $domains
	 */
	public static function matchesDomainList( string $url, array $domains ): bool {
		$bits = self::parseUrl( $url );
		if ( isset( $bits['authority'] ) ) {
			$host = '.' . $bits['authority'];
			foreach ( $domains as $domain ) {
				$domain = '.' . $domain;
				if ( substr( $host, -strlen( $domain ) ) === $domain ) {
					return true;
				}
			}
		}
		return false;
	}
}

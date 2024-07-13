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
 * @author Antoine Musso "<hashar at free dot fr>"
 */

namespace Wikimedia;

use InvalidArgumentException;

/**
 * Play with IP addresses and IP ranges.
 */
class IPUtils {

	/**
	 * An IPv4 address is made of 4 bytes from x00 to xFF which is d0 to d255
	 */
	public const RE_IP_BYTE = '(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|0?[0-9]?[0-9])';

	private const RE_IP_ADD = self::RE_IP_BYTE . '\.' . self::RE_IP_BYTE . '\.'
		. self::RE_IP_BYTE . '\.' . self::RE_IP_BYTE;
	/**
	 * An IPv4 range is an IP address and a prefix (d0 to d32)
	 */
	private const RE_IP_PREFIX = '(3[0-2]|[12][0-9]|[0-9])';

	private const RE_IP_RANGE = '(' . self::RE_IP_ADD . '\/' . self::RE_IP_PREFIX . '|'
		. self::RE_IP_ADD . ' ?\- ?' . self::RE_IP_ADD . ')';

	/**
	 * An IPv6 address is made up of 8 words (each x0000 to xFFFF).
	 * However, the "::" abbreviation can be used on consecutive x0000 words.
	 */
	private const RE_IPV6_WORD = '([0-9A-Fa-f]{1,4})';
	/**
	 * An IPv6 range is an IP address and a prefix (d0 to d128)
	 */
	private const RE_IPV6_PREFIX = '(12[0-8]|1[01][0-9]|[1-9][0-9]|[0-9])';

	private const RE_IPV6_ADD =
		'(?:' .
			// starts with "::" (including "::")
			':(?::|(?::' . self::RE_IPV6_WORD . '){1,7})' .
		'|' .
			// ends with "::" (except "::")
			self::RE_IPV6_WORD . '(?::' . self::RE_IPV6_WORD . '){0,6}::' .
		'|' .
			// contains one "::" in the middle (the ^ makes the test fail if none found)
			self::RE_IPV6_WORD . '(?::((?(-1)|:))?' . self::RE_IPV6_WORD . '){1,6}(?(-2)|^)' .
		'|' .
			// contains no "::"
			self::RE_IPV6_WORD . '(?::' . self::RE_IPV6_WORD . '){7}' .
		')';
	/**
	 * An IPv6 range is an IP address and a prefix (d1 to d128)
	 */
	private const RE_IPV6_RANGE = '(' . self::RE_IPV6_ADD . '\/' . self::RE_IPV6_PREFIX
		. '|' . self::RE_IPV6_ADD . ' ?\- ?' . self::RE_IPV6_ADD . ')';
	/**
	 * For IPv6 canonicalization (NOT for strict validation; these are quite lax!)
	 */
	public const RE_IPV6_GAP = ':(?:0+:)*(?::(?:0+:)*)?';
	/** @private */
	private const RE_IPV6_V4_PREFIX = '0*' . self::RE_IPV6_GAP . '(?:ffff:)?';

	/**
	 * This might be useful for regexps used elsewhere, matches any IPv4 or IPv6 address or network
	 */
	private const RE_IP_ADDRESS_STRING =
		'(?:' .
			// IPv4
			self::RE_IP_ADD . '(?:\/' . self::RE_IP_PREFIX . ')?' .
		'|' .
			// IPv6
			self::RE_IPV6_ADD . '(?:\/' . self::RE_IPV6_PREFIX . ')?' .
		')';

	/**
	 * Maximum number of IP addresses that can be retrieved from a given range.
	 */
	private const MAXIMUM_IPS_FROM_RANGE = 2 ** 16;

	/**
	 * Determine if a string is as valid IP address or network (CIDR prefix).
	 * SIIT IPv4-translated addresses are rejected.
	 * @note canonicalize() tries to convert translated addresses to IPv4.
	 *
	 * @param string $ip Possible IP address
	 * @return bool
	 */
	public static function isIPAddress( $ip ) {
		return (bool)preg_match( '/^' . self::RE_IP_ADDRESS_STRING . '$/', $ip );
	}

	/**
	 * Given a string, determine if it as valid IP in IPv6 only.
	 * @note Unlike isValid(), this looks for networks too.
	 *
	 * @param string $ip Possible IP address
	 * @return bool
	 */
	public static function isIPv6( $ip ) {
		return (bool)preg_match( '/^' . self::RE_IPV6_ADD . '(?:\/' . self::RE_IPV6_PREFIX . ')?$/', $ip );
	}

	/**
	 * Given a string, determine if it as valid IP in IPv4 only.
	 * @note Unlike isValid(), this looks for networks too.
	 *
	 * @param string $ip Possible IP address
	 * @return bool
	 */
	public static function isIPv4( $ip ) {
		return (bool)preg_match( '/^' . self::RE_IP_ADD . '(?:\/' . self::RE_IP_PREFIX . ')?$/', $ip );
	}

	/**
	 * Validate an IP address. Ranges are NOT considered valid.
	 * SIIT IPv4-translated addresses are rejected.
	 * @note canonicalize() tries to convert translated addresses to IPv4.
	 *
	 * @param string $ip
	 * @return bool True if it is valid
	 */
	public static function isValid( $ip ) {
		// Test IPv4 before IPv6 as it's more common.
		return self::isValidIPv4( $ip ) || self::isValidIPv6( $ip );
	}

	/**
	 * Validate an IPv4 address. Ranges are NOT considered valid.
	 *
	 * @param string $ip
	 * @return bool True if it is valid
	 */
	public static function isValidIPv4( $ip ) {
		return (bool)preg_match( '/^' . self::RE_IP_ADD . '$/', $ip );
	}

	/**
	 * Validate an IPv6 address. Ranges are NOT considered valid.
	 * SIIT IPv4-translated addresses are rejected.
	 * @note canonicalize() tries to convert translated addresses to IPv4.
	 *
	 * @param string $ip
	 * @return bool True if it is valid
	 */
	public static function isValidIPv6( $ip ) {
		return (bool)preg_match( '/^' . self::RE_IPV6_ADD . '$/', $ip );
	}

	/**
	 * Validate an IPv4 range (valid IPv4 address with a valid CIDR prefix or explicit range).
	 *
	 * @param string $ipRange
	 * @return bool True if input is valid
	 */
	private static function isValidIPv4Range( $ipRange ) {
		return (bool)preg_match( '/^' . self::RE_IP_RANGE . '$/', $ipRange );
	}

	/**
	 * Validate an IPv6 range (valid IPv6 address with a valid CIDR prefix or explicit range).
	 *
	 * @param string $ipRange
	 * @return bool True if input is valid
	 */
	private static function isValidIPv6Range( $ipRange ) {
		return (bool)preg_match( '/^' . self::RE_IPV6_RANGE . '$/', $ipRange );
	}

	/**
	 * Validate an IP range (valid in either IPv4 OR IPv6; given with valid CIDR prefix or in explicit notation).
	 * SIIT IPv4-translated addresses are rejected.
	 * @note canonicalize() tries to convert translated addresses to IPv4.
	 *
	 * @param string $ipRange
	 * @return bool True if it is valid
	 */
	public static function isValidRange( $ipRange ) {
		// Test IPv4 before IPv6 as it's more common.
		return self::isValidIPv4Range( $ipRange ) || self::isValidIPv6Range( $ipRange );
	}

	/**
	 * Convert an IP into a verbose, uppercase, normalized form.
	 * Both IPv4 and IPv6 addresses are trimmed. Additionally,
	 * IPv6 addresses in octet notation are expanded to 8 words;
	 * IPv4 addresses have leading zeros, in each octet, removed.
	 *
	 * @param string $ip IP address in quad or octet form (CIDR or not).
	 * @return string|null
	 */
	public static function sanitizeIP( $ip ) {
		$ip = trim( $ip );
		if ( $ip === '' ) {
			return null;
		}
		// If not an IP, just return trimmed value, since sanitizeIP() is called
		// in a number of contexts where usernames are supplied as input.
		if ( !self::isIPAddress( $ip ) ) {
			return $ip;
		}
		if ( self::isIPv4( $ip ) ) {
			// Remove leading 0's from octet representation of IPv4 address
			return preg_replace( '!(?:^|(?<=\.))0+(?=[1-9]|0[./]|0$)!', '', $ip );
		}
		// Remove any whitespaces, convert to upper case
		$ip = strtoupper( $ip );
		// Expand zero abbreviations
		$abbrevPos = strpos( $ip, '::' );
		if ( $abbrevPos !== false ) {
			// We know this is valid IPv6. Find the last index of the
			// address before any CIDR number (e.g. "a:b:c::/24").
			$CIDRStart = strpos( $ip, "/" );
			$addressEnd = ( $CIDRStart !== false )
				? $CIDRStart - 1
				: strlen( $ip ) - 1;
			// If the '::' is at the beginning...
			if ( $abbrevPos === 0 ) {
				$repeat = '0:';
				// for the address '::'
				$extra = $ip === '::' ? '0' : '';
				// 7+2 (due to '::')
				$pad = 9;
			// If the '::' is at the end...
			} elseif ( $abbrevPos === $addressEnd - 1 ) {
				$repeat = ':0';
				$extra = '';
				// 7+2 (due to '::')
				$pad = 9;
			// If the '::' is in the middle...
			} else {
				$repeat = ':0';
				$extra = ':';
				// 6+2 (due to '::')
				$pad = 8;
			}
			$ip = str_replace( '::',
				str_repeat( $repeat, $pad - substr_count( $ip, ':' ) ) . $extra,
				$ip
			);
		}
		// Remove leading zeros from each bloc as needed
		return preg_replace( '/(^|:)0+(' . self::RE_IPV6_WORD . ')/', '$1$2', $ip );
	}

	/**
	 * Prettify an IP for display to end users.
	 * This will make it more compact and lower-case.
	 *
	 * @param string $ip
	 * @return string|null
	 */
	public static function prettifyIP( $ip ) {
		// normalize (removes '::')
		$ip = self::sanitizeIP( $ip );
		if ( $ip === null ) {
			return null;
		}
		if ( self::isIPv6( $ip ) ) {
			// Split IP into an address and a CIDR
			if ( strpos( $ip, '/' ) !== false ) {
				[ $ip, $cidr ] = explode( '/', $ip, 2 );
			} else {
				[ $ip, $cidr ] = [ $ip, '' ];
			}
			// Get the largest slice of words with multiple zeros
			$offset = 0;
			$longest = $longestPos = false;
			while ( preg_match(
				'!(?:^|:)0(?::0)+(?:$|:)!', $ip, $m, PREG_OFFSET_CAPTURE, $offset
			) ) {
				// full match
				[ $match, $pos ] = $m[0];
				if ( strlen( (string)$match ) > strlen( (string)$longest ) ) {
					$longest = $match;
					$longestPos = $pos;
				}
				// advance
				$offset = $pos + strlen( $match );
			}
			if ( $longest !== false ) {
				// Replace this portion of the string with the '::' abbreviation
				$ip = substr_replace( $ip, '::', $longestPos, strlen( $longest ) );
			}
			// Add any CIDR back on
			if ( $cidr !== '' ) {
				$ip = "{$ip}/{$cidr}";
			}
			// Convert to lower case to make it more readable
			$ip = strtolower( $ip );
		}

		return $ip;
	}

	/**
	 * Given a host/port string, like one might find in the host part of a URL
	 * per RFC 2732, split the hostname part and the port part and return an
	 * array with an element for each. If there is no port part, the array will
	 * have false in place of the port. If the string was invalid in some way,
	 * false is returned.
	 *
	 * This was easy with IPv4 and was generally done in an ad-hoc way, but
	 * with IPv6 it's somewhat more complicated due to the need to parse the
	 * square brackets and colons.
	 *
	 * A bare IPv6 address is accepted despite the lack of square brackets.
	 *
	 * @param string $both The string with the host (or IPv4/IPv6 address) and port
	 * @return array|false Array normally, false on certain failures
	 */
	public static function splitHostAndPort( $both ) {
		if ( substr( $both, 0, 1 ) === '[' ) {
			if ( preg_match( '/^\[(' . self::RE_IPV6_ADD . ')\](?::(?P<port>\d+))?$/', $both, $m ) ) {
				if ( isset( $m['port'] ) ) {
					return [ $m[1], intval( $m['port'] ) ];
				}

				return [ $m[1], false ];
			}

			// Square bracket found but no IPv6
			return false;
		}
		$numColons = substr_count( $both, ':' );
		if ( $numColons >= 2 ) {
			// Is it a bare IPv6 address?
			if ( preg_match( '/^' . self::RE_IPV6_ADD . '$/', $both ) ) {
				return [ $both, false ];
			}

			// Not valid IPv6, but too many colons for anything else
			return false;
		}
		if ( $numColons >= 1 ) {
			// Host:port?
			$bits = explode( ':', $both );
			if ( preg_match( '/^\d+/', $bits[1] ) ) {
				return [ $bits[0], intval( $bits[1] ) ];
			}

			// Not a valid port
			return false;
		}

		// Plain hostname
		return [ $both, false ];
	}

	/**
	 * Given a host name and a port, combine them into host/port string like
	 * you might find in a URL. If the host contains a colon, wrap it in square
	 * brackets like in RFC 2732. If the port matches the default port, omit
	 * the port specification
	 *
	 * @param string $host
	 * @param int $port
	 * @param bool|int $defaultPort
	 * @return string
	 */
	public static function combineHostAndPort( $host, $port, $defaultPort = false ) {
		if ( strpos( $host, ':' ) !== false ) {
			$host = "[$host]";
		}
		if ( $defaultPort !== false && $port === $defaultPort ) {
			return $host;
		}

		return "$host:$port";
	}

	/**
	 * Convert an IPv4 or IPv6 hexadecimal representation back to readable format
	 *
	 * @param string $hex Number, with "v6-" prefix if it is IPv6
	 * @return string Quad-dotted (IPv4) or octet notation (IPv6)
	 */
	public static function formatHex( $hex ) {
		if ( substr( $hex, 0, 3 ) === 'v6-' ) {
			// IPv6
			return self::hexToOctet( substr( $hex, 3 ) );
		}

		// IPv4
		return self::hexToQuad( $hex );
	}

	/**
	 * Converts a hexadecimal number to an IPv6 address in octet notation
	 *
	 * @param string $ip_hex Pure hex (no v6- prefix)
	 * @return string (of format a:b:c:d:e:f:g:h)
	 */
	public static function hexToOctet( $ip_hex ) {
		// Pad hex to 32 chars (128 bits)
		$ip_hex = str_pad( strtoupper( $ip_hex ), 32, '0', STR_PAD_LEFT );
		// Separate into 8 words
		$ip_oct = substr( $ip_hex, 0, 4 );
		for ( $n = 1; $n < 8; $n++ ) {
			$ip_oct .= ':' . substr( $ip_hex, 4 * $n, 4 );
		}
		// NO leading zeroes
		return preg_replace( '/(^|:)0+(' . self::RE_IPV6_WORD . ')/', '$1$2', $ip_oct );
	}

	/**
	 * Converts a hexadecimal number to an IPv4 address in quad-dotted notation
	 *
	 * @param string $ip_hex Pure hex
	 * @return string (of format a.b.c.d)
	 */
	public static function hexToQuad( $ip_hex ) {
		// Pad hex to 8 chars (32 bits)
		$ip_hex = str_pad( strtoupper( $ip_hex ), 8, '0', STR_PAD_LEFT );
		// Separate into four quads
		$s = '';
		for ( $i = 0; $i < 4; $i++ ) {
			if ( $s !== '' ) {
				$s .= '.';
			}
			$s .= base_convert( substr( $ip_hex, $i * 2, 2 ), 16, 10 );
		}

		return $s;
	}

	/**
	 * Determine if an IP address really is an IP address, and if it is public,
	 * i.e. not RFC 1918 or similar
	 *
	 * @param string $ip
	 * @return bool
	 */
	public static function isPublic( $ip ) {
		static $privateSet = null;
		if ( !$privateSet ) {
			$privateSet = new IPSet( [
				// RFC 1918 (private)
				'10.0.0.0/8',
				// RFC 1918 (private)
				'172.16.0.0/12',
				// RFC 1918 (private)
				'192.168.0.0/16',
				// this network
				'0.0.0.0/8',
				// loopback
				'127.0.0.0/8',
				// RFC 4193 (local)
				'fc00::/7',
				// loopback
				'0:0:0:0:0:0:0:1',
				// link-local
				'169.254.0.0/16',
				// link-local
				'fe80::/10',
			] );
		}
		return !$privateSet->match( $ip );
	}

	/**
	 * Return a zero-padded upper case hexadecimal representation of an IP address.
	 *
	 * Hexadecimal addresses are used because they can easily be extended to
	 * IPv6 support. To separate the ranges, the return value from this
	 * function for an IPv6 address will be prefixed with "v6-", a non-
	 * hexadecimal string which sorts after the IPv4 addresses.
	 *
	 * @param string $ip Quad dotted/octet IP address.
	 * @return string|bool False on failure
	 */
	public static function toHex( $ip ) {
		if ( self::isIPv6( $ip ) ) {
			$n = 'v6-' . self::IPv6ToRawHex( $ip );
		} elseif ( self::isIPv4( $ip ) ) {
			// T62035/T97897: An IP with leading 0's fails in ip2long sometimes (e.g. *.08),
			// also double/triple 0 needs to be changed to just a single 0 for ip2long.
			$ip = self::sanitizeIP( $ip );
			$n = ip2long( $ip );
			if ( $n < 0 ) {
				// We don't run code coverage on a 32-bit OS or Windows, so this will never be exercised
				// @codeCoverageIgnoreStart
				$n += 2 ** 32;
				// On 32-bit platforms (and on Windows), 2^32 does not fit into an int,
				// so $n becomes a float. We convert it to string instead.
				if ( is_float( $n ) ) {
					$n = (string)$n;
				}
				// @codeCoverageIgnoreEnd
			}
			if ( $n !== false ) {
				// Floating points can handle the conversion; faster than \Wikimedia\base_convert()
				$n = strtoupper( str_pad( base_convert( $n, 10, 16 ), 8, '0', STR_PAD_LEFT ) );
			}
		} else {
			$n = false;
		}

		return $n;
	}

	/**
	 * Given an IPv6 address in octet notation, returns a pure hex string.
	 *
	 * @param string $ip Octet ipv6 IP address.
	 * @return string|bool Pure hex (uppercase); false on failure
	 */
	private static function IPv6ToRawHex( $ip ) {
		$ip = self::sanitizeIP( $ip );
		if ( !$ip ) {
			return false;
		}
		$r_ip = '';
		foreach ( explode( ':', $ip ) as $v ) {
			$r_ip .= str_pad( $v, 4, '0', STR_PAD_LEFT );
		}

		return $r_ip;
	}

	/**
	 * Convert a network specification in CIDR notation
	 * to an integer network and a number of bits
	 *
	 * @param string $range IP with CIDR prefix
	 * @return array [int|string, int]
	 */
	public static function parseCIDR( $range ) {
		if ( self::isIPv6( $range ) ) {
			return self::parseCIDR6( $range );
		}
		$parts = explode( '/', $range, 2 );
		if ( count( $parts ) !== 2 ) {
			return [ false, false ];
		}
		[ $network, $bits ] = $parts;
		$network = ip2long( $network );
		if ( $network !== false && is_numeric( $bits ) && $bits >= 0 && $bits <= 32 ) {
			'@phan-var int $bits';
			if ( $bits === 0 ) {
				$network = 0;
			} else {
				$network &= ~( ( 1 << ( 32 - (int)$bits ) ) - 1 );
			}
			// Convert to unsigned
			if ( $network < 0 ) {
				$network += 2 ** 32;
			}
		} else {
			$network = false;
			$bits = false;
		}

		return [ $network, $bits ];
	}

	/**
	 * Given a string range in a number of formats,
	 * return the start and end of the range in hexadecimal.
	 *
	 * Formats are:
	 *     1.2.3.4/24          CIDR
	 *     1.2.3.4 - 1.2.3.5   Explicit range
	 *     1.2.3.4             Single IP
	 *
	 *     2001:0db8:85a3::7344/96                       CIDR
	 *     2001:0db8:85a3::7344 - 2001:0db8:85a3::7344   Explicit range
	 *     2001:0db8:85a3::7344                          Single IP
	 * @param string $range IP range
	 * @return array{string,string}|array{false,false} If the start or end of the range
	 * is invalid, then array `[false, false]` is returned
	 */
	public static function parseRange( $range ) {
		// CIDR notation
		if ( strpos( $range, '/' ) !== false ) {
			if ( self::isIPv6( $range ) ) {
				return self::parseRange6( $range );
			}
			[ $network, $bits ] = self::parseCIDR( $range );
			if ( $network === false ) {
				$start = $end = false;
			} else {
				$start = sprintf( '%08X', $network );
				$end = sprintf( '%08X', $network + 2 ** ( 32 - $bits ) - 1 );
			}
		// Explicit range
		} elseif ( strpos( $range, '-' ) !== false ) {
			[ $start, $end ] = array_map( 'trim', explode( '-', $range, 2 ) );
			if ( self::isIPv6( $start ) && self::isIPv6( $end ) ) {
				return self::parseRange6( $range );
			}
			if ( self::isIPv4( $start ) && self::isIPv4( $end ) ) {
				$start = self::toHex( $start );
				$end = self::toHex( $end );
				if ( $start > $end ) {
					$start = $end = false;
				}
			} else {
				$start = $end = false;
			}
		} else {
			// Single IP
			$start = $end = self::toHex( $range );
		}
		if ( $start === false || $end === false ) {
			return [ false, false ];
		}

		return [ $start, $end ];
	}

	/**
	 * Convert a network specification in IPv6 CIDR notation to an
	 * integer network and a number of bits
	 *
	 * @param string $range
	 *
	 * @return array{string,int}|array{false,false}
	 */
	private static function parseCIDR6( $range ) {
		// Explode into <expanded IP,range>
		$parts = explode( '/', self::sanitizeIP( $range ), 2 );
		if ( count( $parts ) !== 2 ) {
			return [ false, false ];
		}
		[ $network, $bits ] = $parts;
		$network = self::IPv6ToRawHex( $network );
		if ( $network !== false && is_numeric( $bits ) && $bits >= 0 && $bits <= 128 ) {
			'@phan-var int $bits';
			if ( $bits === 0 ) {
				$network = "0";
			} else {
				// Native 32 bit functions WONT work here!!!
				// Convert to a padded binary number
				$network = \Wikimedia\base_convert( $network, 16, 2, 128 );
				// Truncate the last (128-$bits) bits and replace them with zeros
				$network = str_pad( substr( $network, 0, (int)$bits ), 128, '0', STR_PAD_RIGHT );
				// Convert back to an integer
				$network = \Wikimedia\base_convert( $network, 2, 10 );
			}
		} else {
			$network = false;
			$bits = false;
		}

		return [ $network, (int)$bits ];
	}

	/**
	 * Given a string range in a number of formats, return the
	 * start and end of the range in hexadecimal. For IPv6.
	 *
	 * Formats are:
	 *     2001:0db8:85a3::7344/96                       CIDR
	 *     2001:0db8:85a3::7344 - 2001:0db8:85a3::7344   Explicit range
	 *     2001:0db8:85a3::7344/96                       Single IP
	 *
	 * @param string $range
	 *
	 * @return array [string, string]|array [false, false] If the start or end of the range
	 * is invalid, then array [false, false] is returned
	 */
	private static function parseRange6( $range ) {
		// Expand any IPv6 IP
		$range = self::sanitizeIP( $range );

		$start = false;
		$end = false;

		// CIDR notation...
		if ( strpos( $range, '/' ) !== false ) {
			[ $network, $bits ] = self::parseCIDR6( $range );
			if ( $network !== false ) {
				$start = \Wikimedia\base_convert( $network, 10, 16, 32, false );
				// Turn network to binary (again)
				$end = \Wikimedia\base_convert( $network, 10, 2, 128 );
				// Truncate the last (128-$bits) bits and replace them with ones
				$end = str_pad( substr( $end, 0, $bits ), 128, '1', STR_PAD_RIGHT );
				// Convert to hex
				$end = \Wikimedia\base_convert( $end, 2, 16, 32, false );
				// see toHex() comment
				$start = "v6-$start";
				$end = "v6-$end";
			}
		// Explicit range notation...
		} elseif ( strpos( $range, '-' ) !== false ) {
			[ $start, $end ] = array_map( 'trim', explode( '-', $range, 2 ) );
			$start = self::toHex( $start );
			$end = self::toHex( $end );
			if ( $start > $end ) {
				$start = $end = false;
			}
		}

		if ( $start === false || $end === false ) {
			return [ false, false ];
		}

		return [ $start, $end ];
	}

	/**
	 * Determine if a given IPv4/IPv6 address is in a given CIDR network
	 *
	 * @param string $addr The address to check against the given range.
	 * @param string $range The range to check the given address against.
	 * @return bool Whether or not the given address is in the given range.
	 *
	 * @note This can return unexpected results for invalid arguments!
	 *       Make sure you pass a valid IP address and IP range.
	 */
	public static function isInRange( $addr, $range ) {
		$hexIP = self::toHex( $addr );
		[ $start, $end ] = self::parseRange( $range );

		return strcmp( $hexIP, $start ) >= 0 &&
			strcmp( $hexIP, $end ) <= 0;
	}

	/**
	 * Determines if an IP address is a list of CIDR a.b.c.d/n ranges.
	 *
	 * @param string $ip the IP to check
	 * @param array $ranges the IP ranges, each element a range
	 *
	 * @return bool true if the specified adress belongs to the specified range; otherwise, false.
	 */
	public static function isInRanges( $ip, $ranges ) {
		foreach ( $ranges as $range ) {
			if ( self::isInRange( $ip, $range ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Convert some unusual representations of IPv4 addresses to their
	 * canonical dotted quad representation.
	 *
	 * This currently only checks a few IPV4-to-IPv6 related cases. More
	 * unusual representations may be added later.
	 *
	 * @param string $addr Something that might be an IP address
	 * @return string|null Valid IP address or null
	 * @return-taint none
	 */
	public static function canonicalize( $addr ) {
		// remove zone info (T37738)
		$addr = preg_replace( '/\%.*/', '', $addr );

		// If it's already a valid IPv4 address, nothing to do
		if ( self::isValidIPv4( $addr ) ) {
			return $addr;
		}

		// https://en.wikipedia.org/wiki/IPv6#IPv4-mapped_IPv6_addresses
		// Turn mapped addresses from:
		//  ::ce:ffff:1.2.3.4 to 1.2.3.4 (IPv4-mapped IPv6 addresses)
		//  ::1.2.3.4 to 1.2.3.4 (IPv4-compatible IPv6 address)
		// IPv4-compatible IPv6 addresses are now deprecated https://tools.ietf.org/html/rfc4291#section-2.5.5.1
		if ( preg_match( '/^' . self::RE_IPV6_V4_PREFIX . '(' . self::RE_IP_ADD . ')$/i', $addr, $m ) ) {
			return $m[1];
		}

		// Converts :ffff:1F to 255.255.0.31
		// Is this actually used/needed?
		if ( preg_match( '/^' . self::RE_IPV6_V4_PREFIX . self::RE_IPV6_WORD .
			':' . self::RE_IPV6_WORD . '$/i', $addr, $m )
		) {
			return long2ip( ( hexdec( $m[1] ) << 16 ) + hexdec( $m[2] ) );
		}

		// It's a valid IPv6 address that we haven't canonicalized, so return it
		if ( self::isValidIPv6( $addr ) ) {
			return $addr;
		}

		// Not a valid IP address
		return null;
	}

	/**
	 * Gets rid of unneeded numbers in quad-dotted/octet IP strings
	 * For example, 127.111.113.151/24 -> 127.111.113.0/24
	 * @param string $range IP address to normalize
	 * @return string
	 */
	public static function sanitizeRange( $range ) {
		[ , $bits ] = self::parseCIDR( $range );
		[ $start, ] = self::parseRange( $range );
		$start = self::formatHex( $start );
		if ( $bits === false ) {
			// wasn't actually a range
			return $start;
		}

		return "$start/$bits";
	}

	/**
	 * Returns the subnet of a given IP
	 *
	 * @param string $ip
	 * @return string|false
	 */
	public static function getSubnet( $ip ) {
		$matches = [];
		$subnet = false;
		if ( self::isIPv6( $ip ) ) {
			$parts = self::parseRange( "$ip/64" );
			$subnet = $parts[0];
		} elseif ( preg_match( '/^' . self::RE_IP_ADD . '$/', $ip, $matches ) ) {
			// IPv4
			$subnet = "{$matches[1]}.{$matches[2]}.{$matches[3]}";
		}
		return $subnet;
	}

	/**
	 * Return all the addresses in a given range
	 *
	 * This currently does not support IPv6 ranges and is limited to /16 block (65535 addresses).
	 *
	 * @param string $range IP ranges to get the IPs within
	 * @return string[] Array of addresses in the range
	 * @throws InvalidArgumentException If input uses IPv6
	 * @throws InvalidArgumentException If input range is too large
	 */
	public static function getIPsInRange( $range ): array {
		// No IPv6 for now.
		if ( self::isValidIPv6( $range ) || self::isValidIPv6Range( $range ) ) {
			throw new InvalidArgumentException( 'Cannot retrieve addresses for IPv6 range: ' . $range );
		}

		[ $start, $end ] = self::parseRange( $range );
		if ( $start === false || $start === $end ) {
			throw new InvalidArgumentException( 'Invalid range given: ' . $range );
		}

		if ( hexdec( $end ) - hexdec( $start ) > self::MAXIMUM_IPS_FROM_RANGE ) {
			throw new InvalidArgumentException( "Range {$range} is too large, it contains more than "
				. self::MAXIMUM_IPS_FROM_RANGE . ' addresses' );
		}

		$start = ip2long( self::formatHex( $start ) );
		$end = ip2long( self::formatHex( $end ) );

		return array_map( 'long2ip', range( $start, $end ) );
	}
}

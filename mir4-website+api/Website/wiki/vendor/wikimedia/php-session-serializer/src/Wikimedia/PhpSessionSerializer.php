<?php
/**
 * Wikimedia\PhpSessionSerializer
 *
 * Copyright (C) 2015 Brad Jorsch <bjorsch@wikimedia.org>
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
 * @author Brad Jorsch <bjorsch@wikimedia.org>
 */

namespace Wikimedia;

use DomainException;
use Exception;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use UnexpectedValueException;
use Wikimedia\AtEase\AtEase;

/**
 * Provides for encoding and decoding session arrays to PHP's serialization
 * formats.
 *
 * Supported formats are:
 * - php
 * - php_binary
 * - php_serialize
 *
 * WDDX is not supported, since it breaks on all sorts of things.
 */
class PhpSessionSerializer {
	/** @var LoggerInterface */
	protected static $logger;

	/**
	 * Set the logger to which to log
	 * @param LoggerInterface $logger The logger
	 */
	public static function setLogger( LoggerInterface $logger ) {
		self::$logger = $logger;
	}

	/**
	 * Try to set session.serialize_handler to a supported format
	 *
	 * This may change the format even if the current format is also supported.
	 *
	 * @return string Format set
	 * @throws DomainException
	 */
	public static function setSerializeHandler() {
		$formats = [
			'php_serialize',
			'php',
			'php_binary',
		];

		// First, try php_serialize since that's the only one that doesn't suck in some way.
		AtEase::suppressWarnings();
		ini_set( 'session.serialize_handler', 'php_serialize' );
		AtEase::restoreWarnings();
		if ( ini_get( 'session.serialize_handler' ) === 'php_serialize' ) {
			return 'php_serialize';
		}

		// Next, just use the current format if it's supported.
		$format = ini_get( 'session.serialize_handler' );
		if ( in_array( $format, $formats, true ) ) {
			return $format;
		}

		// Last chance, see if any of our supported formats are accepted.
		foreach ( $formats as $format ) {
			AtEase::suppressWarnings();
			ini_set( 'session.serialize_handler', $format );
			AtEase::restoreWarnings();
			if ( ini_get( 'session.serialize_handler' ) === $format ) {
				return $format;
			}
		}

		throw new DomainException(
			'Failed to set serialize handler to a supported format.' .
				' Supported formats are: ' . implode( ', ', $formats ) . '.'
		);
	}

	/**
	 * Encode a session array to a string, using the format in session.serialize_handler
	 * @param array $data Session data
	 * @return string|null Encoded string, or null on failure
	 * @throws DomainException
	 */
	public static function encode( array $data ) {
		$format = ini_get( 'session.serialize_handler' );
		if ( !is_string( $format ) ) {
			throw new UnexpectedValueException(
				'Could not fetch the value of session.serialize_handler'
			);
		}
		switch ( $format ) {
			case 'php':
				return self::encodePhp( $data );

			case 'php_binary':
				return self::encodePhpBinary( $data );

			case 'php_serialize':
				return self::encodePhpSerialize( $data );

			default:
				throw new DomainException( "Unsupported format \"$format\"" );
		}
	}

	/**
	 * Decode a session string to an array, using the format in session.serialize_handler
	 * @param string $data Session data. Use the same caution in passing
	 *   user-controlled data here that you would to PHP's unserialize function.
	 * @return array|null Data, or null on failure
	 * @throws DomainException
	 * @throws InvalidArgumentException
	 */
	public static function decode( $data ) {
		if ( !is_string( $data ) ) {
			throw new InvalidArgumentException( '$data must be a string' );
		}

		$format = ini_get( 'session.serialize_handler' );
		if ( !is_string( $format ) ) {
			throw new UnexpectedValueException(
				'Could not fetch the value of session.serialize_handler'
			);
		}
		switch ( $format ) {
			case 'php':
				return self::decodePhp( $data );

			case 'php_binary':
				return self::decodePhpBinary( $data );

			case 'php_serialize':
				return self::decodePhpSerialize( $data );

			default:
				throw new DomainException( "Unsupported format \"$format\"" );
		}
	}

	/**
	 * Serialize a value with error logging
	 * @param mixed $value
	 * @return string|null
	 */
	private static function serializeValue( $value ) {
		try {
			return serialize( $value );
		} catch ( Exception $ex ) {
			self::$logger->error( 'Value serialization failed: ' . $ex->getMessage() );
			return null;
		}
	}

	/**
	 * Unserialize a value with error logging
	 * @param string &$string On success, the portion used is removed
	 * @return array ( bool $success, mixed $value )
	 */
	private static function unserializeValue( &$string ) {
		$error = null;
		set_error_handler( static function ( $errno, $errstr ) use ( &$error ) {
			$error = $errstr;
			return true;
		} );
		$ret = unserialize( $string );
		restore_error_handler();

		if ( $error !== null ) {
			self::$logger->error( 'Value unserialization failed: ' . $error );
			return [ false, null ];
		}

		$serialized = serialize( $ret );
		$l = strlen( $serialized );
		if ( substr( $string, 0, $l ) !== $serialized ) {
			self::$logger->error(
				'Value unserialization failed: read value does not match original string'
			);
			return [ false, null ];
		}

		$string = substr( $string, $l );
		return [ true, $ret ];
	}

	/**
	 * Encode a session array to a string in 'php' format
	 * @note Generally you'll use self::encode() instead of this method.
	 * @param array $data Session data
	 * @return string|null Encoded string, or null on failure
	 */
	public static function encodePhp( array $data ) {
		$ret = '';
		foreach ( $data as $key => $value ) {
			// @phan-suppress-next-line PhanTypeMismatchArgumentInternal
			if ( strcmp( $key, intval( $key ) ) === 0 ) {
				self::$logger->warning( "Ignoring unsupported integer key \"$key\"" );
				continue;
			}
			if ( strcspn( $key, '|!' ) !== strlen( $key ) ) {
				self::$logger->error( "Serialization failed: Key with unsupported characters \"$key\"" );
				return null;
			}
			$v = self::serializeValue( $value );
			if ( $v === null ) {
				return null;
			}
			$ret .= "$key|$v";
		}
		return $ret;
	}

	/**
	 * Decode a session string in 'php' format to an array
	 * @note Generally you'll use self::decode() instead of this method.
	 * @param string $data Session data. Use the same caution in passing
	 *   user-controlled data here that you would to PHP's unserialize function.
	 * @return array|null Data, or null on failure
	 * @throws InvalidArgumentException
	 */
	public static function decodePhp( $data ) {
		if ( !is_string( $data ) ) {
			throw new InvalidArgumentException( '$data must be a string' );
		}

		$ret = [];
		while ( $data !== '' && $data !== false ) {
			$i = strpos( $data, '|' );
			if ( $i === false ) {
				if ( substr( $data, -1 ) !== '!' ) {
					self::$logger->warning( 'Ignoring garbage at end of string' );
				}
				break;
			}

			$key = substr( $data, 0, $i );
			$data = substr( $data, $i + 1 );

			if ( strpos( $key, '!' ) !== false ) {
				self::$logger->warning( "Decoding found a key with unsupported characters: \"$key\"" );
			}

			if ( $data === '' || $data === false ) {
				self::$logger->error( 'Unserialize failed: unexpected end of string' );
				return null;
			}

			list( $ok, $value ) = self::unserializeValue( $data );
			if ( !$ok ) {
				return null;
			}
			$ret[$key] = $value;
		}
		return $ret;
	}

	/**
	 * Encode a session array to a string in 'php_binary' format
	 * @note Generally you'll use self::encode() instead of this method.
	 * @param array $data Session data
	 * @return string|null Encoded string, or null on failure
	 */
	public static function encodePhpBinary( array $data ) {
		$ret = '';
		foreach ( $data as $key => $value ) {
			// @phan-suppress-next-line PhanTypeMismatchArgumentInternal
			if ( strcmp( $key, intval( $key ) ) === 0 ) {
				self::$logger->warning( "Ignoring unsupported integer key \"$key\"" );
				continue;
			}
			$l = strlen( $key );
			if ( $l > 127 ) {
				self::$logger->warning( "Ignoring overlong key \"$key\"" );
				continue;
			}
			$v = self::serializeValue( $value );
			if ( $v === null ) {
				return null;
			}
			$ret .= chr( $l ) . $key . $v;
		}
		return $ret;
	}

	/**
	 * Decode a session string in 'php_binary' format to an array
	 * @note Generally you'll use self::decode() instead of this method.
	 * @param string $data Session data. Use the same caution in passing
	 *   user-controlled data here that you would to PHP's unserialize function.
	 * @return array|null Data, or null on failure
	 * @throws InvalidArgumentException
	 */
	public static function decodePhpBinary( $data ) {
		if ( !is_string( $data ) ) {
			throw new InvalidArgumentException( '$data must be a string' );
		}

		$ret = [];
		while ( $data !== '' && $data !== false ) {
			$l = ord( $data[0] );
			if ( strlen( $data ) < ( $l & 127 ) + 1 ) {
				self::$logger->error( 'Unserialize failed: unexpected end of string' );
				return null;
			}

			// "undefined" marker
			if ( $l > 127 ) {
				$data = substr( $data, ( $l & 127 ) + 1 );
				continue;
			}

			$key = substr( $data, 1, $l );
			$data = substr( $data, $l + 1 );
			if ( $data === '' || $data === false ) {
				self::$logger->error( 'Unserialize failed: unexpected end of string' );
				return null;
			}

			list( $ok, $value ) = self::unserializeValue( $data );
			if ( !$ok ) {
				return null;
			}
			$ret[$key] = $value;
		}
		return $ret;
	}

	/**
	 * Encode a session array to a string in 'php_serialize' format
	 * @note Generally you'll use self::encode() instead of this method.
	 * @param array $data Session data
	 * @return string|null Encoded string, or null on failure
	 */
	public static function encodePhpSerialize( array $data ) {
		try {
			return serialize( $data );
		} catch ( Exception $ex ) {
			self::$logger->error( 'PHP serialization failed: ' . $ex->getMessage() );
			return null;
		}
	}

	/**
	 * Decode a session string in 'php_serialize' format to an array
	 * @note Generally you'll use self::decode() instead of this method.
	 * @param string $data Session data. Use the same caution in passing
	 *   user-controlled data here that you would to PHP's unserialize function.
	 * @return array|null Data, or null on failure
	 * @throws InvalidArgumentException
	 */
	public static function decodePhpSerialize( $data ) {
		if ( !is_string( $data ) ) {
			throw new InvalidArgumentException( '$data must be a string' );
		}

		$error = null;
		set_error_handler( static function ( $errno, $errstr ) use ( &$error ) {
			$error = $errstr;
			return true;
		} );
		$ret = unserialize( $data );
		restore_error_handler();

		if ( $error !== null ) {
			self::$logger->error( 'PHP unserialization failed: ' . $error );
			return null;
		}

		// PHP strangely allows non-arrays to session_decode(), even though
		// that breaks $_SESSION. Let's not do that.
		if ( !is_array( $ret ) ) {
			self::$logger->error( 'PHP unserialization failed (value was not an array)' );
			return null;
		}

		return $ret;
	}

}

PhpSessionSerializer::setLogger( new \Psr\Log\NullLogger() ); // @codeCoverageIgnore

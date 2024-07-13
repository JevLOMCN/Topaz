<?php
declare( strict_types=1 );

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

namespace Wikimedia\JsonCodec;

use Exception;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use stdClass;

/**
 * Helper class to serialize/unserialize things to/from JSON.
 */
class JsonCodec implements JsonCodecInterface {
	/** @var ContainerInterface Service container */
	protected ContainerInterface $serviceContainer;

	/** @var array<class-string,JsonClassCodec> Class codecs */
	protected array $codecs;

	/**
	 * Name of the property where class information is stored; it also
	 * is used to mark "complex" arrays, and as a place to store the contents
	 * of any pre-existing array property that happened to have the same name.
	 */
	private const TYPE_ANNOTATION = '_type_';

	/**
	 * @param ?ContainerInterface $serviceContainer
	 */
	public function __construct( ?ContainerInterface $serviceContainer = null ) {
		$this->serviceContainer = $serviceContainer ??
			// Use an empty container if none is provided.
			new class implements ContainerInterface {
				/**
				 * @param string $id
				 * @return never
				 */
				public function get( $id ) {
					throw new class( "not found" ) extends Exception implements NotFoundExceptionInterface {
					};
				}

				/** @inheritDoc */
				public function has( string $id ): bool {
					return false;
				}
			};
		$this->addCodecFor(
			stdClass::class, JsonStdClassCodec::getInstance()
		);
	}

	/**
	 * Recursively converts a given object to a JSON-encoded string.
	 * While serializing the $value JsonCodec delegates to the appropriate
	 * JsonClassCodecs of any classes which implement JsonCodecable.
	 *
	 * If a $classHint is provided and matches the type of the value,
	 * then type information will not be included in the generated JSON;
	 * otherwise an appropriate class name will be added to the JSON to
	 * guide deserialization.
	 *
	 * @param mixed|null $value
	 * @param ?class-string $classHint An optional hint to
	 *   the type of the encoded object.  If this is provided and matches
	 *   the type of $value, then explicit type information will be omitted
	 *   from the generated JSON, which saves some space.
	 * @return string
	 */
	public function toJsonString( $value, ?string $classHint = null ): string {
		return json_encode(
			$this->toJsonArray( $value, $classHint ),
			JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE |
			JSON_HEX_TAG | JSON_HEX_AMP
		);
	}

	/**
	 * Recursively converts a JSON-encoded string to an object value or scalar.
	 * While deserializing the $json JsonCodec delegates to the appropriate
	 * JsonClassCodecs of any classes which implement JsonCodecable.
	 *
	 * For objects encoded using implicit class information, a "class hint"
	 * can be provided to guide deserialization; this is unnecessary for
	 * objects serialized with explicit classes.
	 *
	 * @param string $json A JSON-encoded string
	 * @param ?class-string $classHint An optional hint to
	 *   the type of the encoded object.  In the absence of explicit
	 *   type information in the JSON, this will be used as the type of
	 *   the created object.
	 * @return mixed|null
	 */
	public function newFromJsonString( $json, ?string $classHint = null ) {
		return $this->newFromJsonArray(
			json_decode( $json, true ), $classHint
		);
	}

	/**
	 * Maintain a cache giving the codec for a given class name.
	 *
	 * Reusing this JsonCodec object will also reuse this cache, which
	 * could improve performance somewhat.
	 *
	 * @param class-string $className
	 * @return ?JsonClassCodec a codec for the class, or null if the class is
	 *   not serializable.
	 */
	protected function codecFor( string $className ): ?JsonClassCodec {
		$codec = $this->codecs[$className] ?? null;
		if ( !$codec ) {
			if ( !is_a( $className, JsonCodecable::class, true ) ) {
				return null;
			}
			$codec = $this->codecs[$className] =
				$className::jsonClassCodec( $this, $this->serviceContainer );
		}
		return $codec;
	}

	/**
	 * Allow the use of a customized encoding for the given class; the given
	 * className need not be a JsonCodecable and if it *does* correspond to
	 * a JsonCodecable it will override the class codec specified by the
	 * JsonCodecable.
	 * @param class-string $className
	 * @param JsonClassCodec $codec A codec to use for $className
	 */
	public function addCodecFor( string $className, JsonClassCodec $codec ): void {
		if ( $this->codecs[$className] ?? false ) {
			throw new InvalidArgumentException(
				"Codec already present for $className"
			);
		}
		$this->codecs[$className] = $codec;
	}

	/**
	 * Recursively converts a given object to an associative array
	 * which can be json-encoded.  (When embeddeding an object into
	 * another context it is sometimes useful to have the array
	 * representation rather than the string JSON form of the array;
	 * this can also be useful if you want to pretty-print the result,
	 * etc.)  While converting $value the JsonCodec delegates to the
	 * appropriate JsonClassCodecs of any classes which implement
	 * JsonCodecable.
	 *
	 * If a $classHint is provided and matches the type of the value,
	 * then type information will not be included in the generated JSON;
	 * otherwise an appropriate class name will be added to the JSON to
	 * guide deserialization.
	 *
	 * @param mixed|null $value
	 * @param ?class-string $classHint An optional hint to
	 *   the type of the encoded object.  If this is provided and matches
	 *   the type of $value, then explicit type information will be omitted
	 *   from the generated JSON, which saves some space.
	 * @return mixed|null
	 */
	public function toJsonArray( $value, ?string $classHint = null ) {
		$is_complex = false;
		$className = 'array';
		$codec = null;
		// Adjust class hint for arrays.
		$arrayClassHint = null;
		if ( $classHint !== null && str_ends_with( $classHint, '[]' ) ) {
			$arrayClassHint = substr( $classHint, 0, -2 );
			$classHint = 'array';
		}
		if ( is_object( $value ) ) {
			$className = get_class( $value );
			$codec = $this->codecFor( $className );
			if ( $codec !== null ) {
				$value = $codec->toJsonArray( $value );
				$is_complex = true;
			}
		} elseif (
			is_array( $value ) && $this->isArrayMarked( $value )
		) {
			$is_complex = true;
		}
		if ( is_array( $value ) ) {
			// Recursively convert array values to serializable form
			foreach ( $value as $key => &$v ) {
				if ( is_object( $v ) || is_array( $v ) ) {
					$propClassHint = $codec === null ? $arrayClassHint :
						// phan can't tell that $codec is null when $className is 'array'
						// @phan-suppress-next-line PhanUndeclaredClassReference
						$codec->jsonClassHintFor( $className, (string)$key );
					$v = $this->toJsonArray( $v, $propClassHint );
					if (
						$this->isArrayMarked( $v ) ||
						$propClassHint !== null
					) {
						// an array which contains complex components is
						// itself complex.
						$is_complex = true;
					}
				}
			}
			// Ok, now mark the array, being careful to transfer away
			// any fields with the same names as our markers.
			if ( $is_complex || $classHint !== null ) {
				// Even if $className === $classHint we need to record this
				// array as "complex" (ie, requires recursion to process
				// individual values during deserialization)
				// @phan-suppress-next-line PhanUndeclaredClassReference 'array'
				$this->markArray(
					$value, $className, $classHint
				);
			}
		} elseif ( !is_scalar( $value ) && $value !== null ) {
			throw new InvalidArgumentException(
				'Unable to serialize JSON.'
			);
		}
		return $value;
	}

	/**
	 * Recursively converts an associative array (or scalar) to an
	 * object value (or scalar).  While converting this value JsonCodec
	 * delegates to the appropriate JsonClassCodecs of any classes which
	 * implement JsonCodecable.
	 *
	 * For objects encoded using implicit class information, a "class hint"
	 * can be provided to guide deserialization; this is unnecessary for
	 * objects serialized with explicit classes.
	 *
	 * @param mixed|null $json
	 * @param ?class-string $classHint An optional hint to
	 *   the type of the encoded object.  In the absence of explicit
	 *   type information in the JSON, this will be used as the type of
	 *   the created object.
	 * @return mixed|null
	 */
	public function newFromJsonArray( $json, ?string $classHint = null ) {
		if ( $json instanceof stdClass ) {
			// We *shouldn't* be given an object... but we might.
			$json = (array)$json;
		}
		// Adjust class hint for arrays.
		$arrayClassHint = null;
		if ( $classHint !== null && str_ends_with( $classHint, '[]' ) ) {
			$arrayClassHint = substr( $classHint, 0, -2 );
			$classHint = 'array';
		}
		// Is this an array containing a complex value?
		if (
			is_array( $json ) && (
				$this->isArrayMarked( $json ) || $classHint !== null
			)
		) {
			// Read out our metadata
			// @phan-suppress-next-line PhanUndeclaredClassReference 'array'
			$className = $this->unmarkArray( $json, $classHint );
			// Create appropriate codec
			$codec = null;
			if ( $className !== 'array' ) {
				$codec = $this->codecFor( $className );
				if ( $codec === null ) {
					throw new InvalidArgumentException(
						"Unable to deserialize JSON for $className"
					);
				}
			}
			// Recursively unserialize the array contents.
			$unserialized = [];
			foreach ( $json as $key => $value ) {
				$propClassHint = $codec === null ? $arrayClassHint :
					// phan can't tell that $codec is null when $className is 'array'
					// @phan-suppress-next-line PhanUndeclaredClassReference
					$codec->jsonClassHintFor( $className, (string)$key );
				if (
					is_array( $value ) && (
						$this->isArrayMarked( $value ) || $propClassHint !== null
					)
				) {
					$unserialized[$key] = $this->newFromJsonArray( $value, $propClassHint );
				} else {
					$unserialized[$key] = $value;
				}
			}
			// Use a JsonCodec to create the object instance if appropriate.
			if ( $className === 'array' ) {
				$json = $unserialized;
			} else {
				$json = $codec->newFromJsonArray( $className, $unserialized );
			}
		}
		return $json;
	}

	// Functions to mark/unmark arrays and record a class name using a
	// single reserved field, named by self::TYPE_ANNOTATION.  A
	// subclass can provide alternate implementations of these methods
	// if it wants to use a different reserved field or else wishes to
	// reserve more fields/encode certain types more compactly/flag
	// certain types of values.  For example: a subclass could choose
	// to discard all hints in `markArray` in order to explicitly mark
	// all types in preparation for a format change; or all values of
	// type DocumentFragment might get a marker flag added so they can
	// be identified without knowledge of the class hint; or perhaps a
	// separate schema can be used to record class names more
	// compactly.

	/**
	 * Determine if the given value is "marked"; that is, either
	 * represents a object type encoded using a JsonClassCodec or else
	 * is an array which contains values (or contains arrays
	 * containing values, etc) which are object types. The values of
	 * unmarked arrays are not decoded, in order to speed up the
	 * decoding process.  Arrays may also be marked even if they do
	 * not represent object types (or an array recursively containing
	 * them) if they contain keys that need to be escaped ("false
	 * marks"); as such this method is called both on the raw results
	 * of JsonClassCodec (to check for "false marks") as well as on
	 * encoded arrays (to find "true marks").
	 *
	 * Arrays do not have to be marked if the decoder has a class hint.
	 *
	 * @param array $value An array result from `JsonClassCodec::toJsonArray()`,
	 *  or an array result from `::markArray()`
	 * @return bool Whether the $value is marked
	 */
	protected function isArrayMarked( array $value ): bool {
		return array_key_exists( self::TYPE_ANNOTATION, $value );
	}

	/**
	 * Record a mark in the array, reversibly.
	 *
	 * The mark should record the class name, if it is different from
	 * the class hint.  The result does not need to trigger
	 * `::isArrayMarked` if there is an accurate class hint present,
	 * but otherwise the result should register as marked.  The
	 * provided value may be a "complex" array (one that recursively
	 * contains encoded object) or an array with a "false mark"; in
	 * both cases the provided $className will be `array`.
	 *
	 * @param array &$value An array result from `JsonClassCodec::toJsonArray()`
	 *   or a "complex" array
	 * @param 'array'|class-string $className The name of the class encoded
	 *   by the codec, or else `array` if $value is a "complex" array or a
	 *   "false mark"
	 * @param class-string|'array'|null $classHint The class name provided as
	 *   a hint to the encoder, and which will be in turn provided as a hint
	 *   to the decoder, or `null` if no hint was provided.  The class hint
	 *   will be `array` when the array is a homogeneous list of objects.
	 */
	protected function markArray( array &$value, string $className, ?string $classHint ): void {
		// We're going to use an array key, but first we have to see whether it
		// was already present in the array we've been given, in which case
		// we need to escape it (by hoisting into a child array).
		if ( array_key_exists( self::TYPE_ANNOTATION, $value ) ) {
			if ( $className !== $classHint ) {
				$value[self::TYPE_ANNOTATION] = [ $value[self::TYPE_ANNOTATION], $className ];
			} else {
				// Omit $className since it matches the $classHint, but we still
				// need to escape the field to make it clear it was marked.
				// (If the class hint hadn't matched, the proper class name
				// would be here in an array, and we need to distinguish that
				// case from the case where the "actual value" is an array.)
				$value[self::TYPE_ANNOTATION] = [ $value[self::TYPE_ANNOTATION] ];
			}
		} elseif (
			$className !== $classHint ||
			( array_is_list( $value ) && $className !== 'array' )
		) {
			// Include the type annotation if it doesn't match the hint;
			// but also include it if necessary to break up a list. This
			// ensures that all objects have an encoding in the '{...}' style,
			// even if they happen to have all-numeric keys.
			$value[self::TYPE_ANNOTATION] = $className;
		}
	}

	/**
	 * Remove a mark from an encoded array, and return an
	 * encoded class name if present.
	 *
	 * The provided array may not trigger `::isArrayMarked` is there
	 * was a class hint provided.
	 *
	 * If the provided array had a "false mark" or recursively
	 * contained objects, the returned class name should be 'array'.
	 *
	 * @param array &$value An encoded array
	 * @param 'array'|class-string|null $classHint The class name provided as a hint to
	 *  the decoder, which was previously provided as a hint to the encoder,
	 *   or `null` if no hint was provided.
	 * @return 'array'|class-string The class name to be used for decoding, or
	 *  'array' if the value was a "complex" or "false mark" array.
	 */
	protected function unmarkArray( array &$value, ?string $classHint ): string {
		$className = $value[self::TYPE_ANNOTATION] ?? $classHint;
		// Remove our marker and restore the previous state of the
		// json array (restoring a pre-existing field if needed)
		if ( is_array( $className ) ) {
			$value[self::TYPE_ANNOTATION] = $className[0];
			$className = $className[1] ?? $classHint;
		} else {
			unset( $value[self::TYPE_ANNOTATION] );
		}
		return $className;
	}

}

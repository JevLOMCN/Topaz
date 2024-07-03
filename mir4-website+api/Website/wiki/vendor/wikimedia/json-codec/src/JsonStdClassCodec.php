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

/**
 * This is a simple class codec used for `stdClass` objects.
 * @internal
 */
// This class should @implements JsonClassCodec<stdClass> but phan's incomplete
// support for generics doesn't allow that yet.
class JsonStdClassCodec implements JsonClassCodec {

	/**
	 * Returns a JSON array representing the contents of the given object, that
	 * can be deserialized with the corresponding newFromJsonArray() method,
	 * using a ::toJsonArray() method on the object itself.
	 *
	 * @param object $obj An object of the type handled by this JsonClassCodec
	 * @return array A Json representation of the object.
	 * @inheritDoc
	 * @see JsonCodecableTrait
	 */
	public function toJsonArray( $obj ): array {
		return (array)$obj;
	}

	/**
	 * Creates a new instance of the given class and initializes it from the
	 * $json array, using a static method on $className.
	 * @param class-string<T> $className
	 * @param array $json
	 * @return T
	 * @inheritDoc
	 * @phan-template T
	 */
	public function newFromJsonArray( string $className, array $json ) {
		// @phan-suppress-next-line PhanTypeMismatchReturn inadequate generics
		return (object)$json;
	}

	/**
	 * Returns null, to indicate no type hint for any properties in the
	 * `stdClass` value being encoded.
	 *
	 * @param class-string<T> $className
	 * @param string $keyName
	 * @return null Always returns null
	 */
	public function jsonClassHintFor( string $className, string $keyName ): ?string {
		return null;
	}

	/**
	 * Return a singleton instance of this stdClass codec.
	 * @return JsonStdClassCodec a singleton instance of this class
	 */
	public static function getInstance(): JsonStdClassCodec {
		static $instance = null;
		if ( $instance == null ) {
			$instance = new JsonStdClassCodec();
		}
		return $instance;
	}
}

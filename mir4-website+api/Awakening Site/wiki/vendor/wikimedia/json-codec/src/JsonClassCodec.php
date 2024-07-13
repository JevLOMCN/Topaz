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
 * Classes implementing this interface support round-trip JSON
 * serialization/deserialization for certain class types.
 * They may maintain state and/or consult service objects which
 * are stored in the codec object.
 *
 *
 * @template T
 */
interface JsonClassCodec {

	/**
	 * Returns a JSON array representing the contents of the given object, that
	 * can be deserialized with the corresponding newFromJsonArray() method.
	 *
	 * The returned array can contain other JsonCodecables as values;
	 * the JsonCodec class will take care of encoding values in the array
	 * as needed, as well as annotating the returned array with the class
	 * information needed to locate the correct ::newFromJsonArray()
	 * method during deserialization.
	 *
	 * Only objects of the types registered to this JsonClassCodec will be
	 * provided to this method.
	 *
	 * @param T $obj An object of the type handled by this JsonClassCodec
	 * @return array A Json representation of the object.
	 */
	public function toJsonArray( $obj ): array;

	/**
	 * Creates a new instance of the given class and initializes it from the
	 * $json array.
	 * @param class-string<T> $className
	 * @param array $json
	 * @return T
	 */
	public function newFromJsonArray( string $className, array $json );

	/**
	 * Return an optional type hint for the given array key in the result of
	 * ::toJsonArray() / input to ::newFromJsonArray.  If a class name is
	 * returned here and it matches the runtime type of the value of that
	 * array key, then type information will be omitted from the generated
	 * JSON which can save space.  The class name can be suffixed with `[]`
	 * to indicate an array or list containing objects of the given class
	 * name.
	 *
	 * @param class-string<T> $className The class we're looking for a hint for
	 * @param string $keyName The name of the array key we'd like a hint on
	 * @return class-string|string|null A class string, a class string suffixed
	 *   with `[]`, or null
	 */
	public function jsonClassHintFor( string $className, string $keyName ): ?string;
}

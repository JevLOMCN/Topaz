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

use Psr\Container\ContainerInterface;
use stdClass;

/**
 * The JsonCodecableTrait aids in the implementation of stateless codecs.
 * The class using the trait need only define stateless ::toJsonArray() and
 * ::newFromJsonArray() methods.
 *
 * The class using the trait should also implement JsonCodecable
 * (https://wiki.php.net/rfc/traits-with-interfaces may allow the trait
 * to do this directly in a future PHP version).
 */
trait JsonCodecableTrait {

	/**
	 * Implements JsonCodecable by providing an implementation of
	 * ::jsonClassCodec() which does not use the provided $serviceContainer
	 * nor does it maintain any state; it just calls the ::toJsonArray()
	 * and ::newFromJsonArray() methods of this instance.
	 * @param JsonCodecInterface $codec
	 * @param ContainerInterface $serviceContainer
	 * @return JsonClassCodec
	 */
	public static function jsonClassCodec(
		JsonCodecInterface $codec, ContainerInterface $serviceContainer
	): JsonClassCodec {
		// In advanced JIT implementations optimization of the method
		// dispatch in this class can be performed if we keep the
		// codecs for each class separate.  However, for simplicity
		// (and to reduce memory usage) we'll use a singleton object
		// shared with all classes which use this trait.
		return JsonStaticClassCodec::getInstance();
	}

	/**
	 * Return an associative array representing the contents of this object,
	 * which can be passed to ::newFromJsonArray() to deserialize it.
	 * @return array
	 */
	abstract public function toJsonArray(): array;

	/**
	 * Return an instance of this object representing the deserialization
	 * from the array passed in $json.
	 * @param array $json
	 * @return stdClass
	 */
	abstract public static function newFromJsonArray( array $json );

	/**
	 * Return an optional type hint for the given array key in the result of
	 * ::toJsonArray() / input to ::newFromJsonArray.  If a class name is
	 * returned here and it matches the runtime type of the value of that
	 * array key, then type information will be omitted from the generated
	 * JSON which can save space.  The class name can be suffixed with `[]`
	 * to indicate an array or list containing objects of the given class
	 * name.
	 *
	 * Default implementation of ::jsonClassHintFor() provides no hints.
	 * Implementer can override.
	 *
	 * @param string $keyName
	 * @return class-string|string|null A class string, a class string suffixed
	 *   with `[]`, or null
	 */
	public static function jsonClassHintFor( string $keyName ): ?string {
		return null;
	}
}

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
 * Interface used to serialize/unserialize things to/from JSON.  This
 * interface only contains the two fundamental methods from JsonCodec,
 * and is intended to be used (when necessary) by JsonClassCodec
 * implementations which need to manually serialize/deserialize components
 * of their representation.  For example:
 * ```
 * class FooCodec extends JsonClassCodec {
 *   private JsonCodecInterface $codec;
 *   ...
 *   public function newFromJsonArray( string $className, array $json ): Foo {
 *       $tag = $json['tag'];
 *       // Based on the $tag we can infer the appropriate type for $value
 *       // and don't need to explicitly include it in $json:
 *       switch ($tag) {
 *       case 'bar':
 *         $value = $this->codec->newFromJsonArray( $json['value'], Bar::class );
 *         break;
 *       case 'bat':
 *         $value = $this->codec->newFromJsonArray( $json['value'], Bat::class );
 *         break;
 *       ...
 *       }
 *       return new Foo($tag, $value);
 *   }
 * }
 * ```
 * Generally speaking, explicitly invoking the codec to deserialize properties
 * of $json is not required; the deserialization is handled automatically
 * using the type annotations embedded in the JSON.  This style of explicit
 * serialization/deserialization is only necessary when implicit types are
 * used, and they are used in a manner which can't be represented by
 * JsonClassCodec::jsonClassHintFor().  In addition to tagged unions like the
 * above example, implicit types for objects embedded within array components
 * might be another use case.
 */
interface JsonCodecInterface {
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
	public function toJsonArray( $value, ?string $classHint = null );

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
	public function newFromJsonArray( $json, ?string $classHint = null );
}

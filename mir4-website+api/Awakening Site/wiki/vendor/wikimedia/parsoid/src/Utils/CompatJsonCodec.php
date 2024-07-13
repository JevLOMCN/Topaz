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
namespace Wikimedia\Parsoid\Utils;

use JsonSerializable;
use Wikimedia\JsonCodec\JsonClassCodec;
use Wikimedia\JsonCodec\JsonCodec;

/**
 * This is a "compatible" JSON codec for the use of Parsoid test runners, etc.
 * In addition to supporting objects which implement `JsonCodecable`, it
 * tries to handle objects we might get from mediawiki-core which implement
 * JsonSerializable and other legacy serialization types.
 *
 * This should not be relied on for production!
 *
 * However, it is good enough to use in test cases, etc, and hopefully makes
 * them a little bit less fragile by not blowing up if it gets a martian
 * object from mediawiki-core stuck into the parser's extension data.
 */
class CompatJsonCodec extends JsonCodec {
	/** @inheritDoc */
	protected function codecFor( string $className ): ?JsonClassCodec {
		$codec = parent::codecFor( $className );
		if ( $codec === null && is_a( $className, JsonSerializable::class, true ) ) {
			$codec = new class() implements JsonClassCodec {
				/** @inheritDoc */
				public function toJsonArray( $obj ): array {
					return $obj->jsonSerialize();
				}

				/**
				 * @param class-string $className
				 * @param array $json
				 * @return never
				 */
				public function newFromJsonArray( string $className, array $json ) {
					// We can't use the core JsonUnserializable interface
					// (even blindly) because we can't make a non-null
					// JsonUnserializer which is required as the first argument
					// T346829, T327439#8634426
					// That's ok, though, we can still *serialize* objects for
					// test cases even if we can't unserialize them.
					throw new \InvalidArgumentException( "Unserialization of this $className not possible" );
				}

				/** @inheritDoc */
				public function jsonClassHintFor( string $className, string $keyName ): ?string {
					return null;
				}
			};
			// Cache this for future use
			$this->addCodecFor( $className, $codec );
		}
		return $codec;
	}
}

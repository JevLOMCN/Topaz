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

/**
 * Classes implementing this interface support round-trip JSON
 * serialization/deserialization via a JsonClassCodec object
 * (which may maintain state and/or consult service objects).
 * It requires a single static method to be defined which
 * allows the creation of an appropriate JsonClassCodec
 * for this class.
 */
interface JsonCodecable {

	/**
	 * Create a JsonClassCodec which can serialize/deserialize instances of
	 * this class.
	 * @param JsonCodecInterface $codec A codec which can be used to handle
	 *  certain cases of implicit typing in the generated JSON; see
	 *  `JsonCodecInterface` for details.  It should not be necessary for
	 *  most class codecs to use this, as recursive
	 *  serialization/deserialization is handled by default.
	 * @param ContainerInterface $serviceContainer A service container
	 * @return JsonClassCodec A JsonClassCodec appropriate for objects of
	 *  this type.
	 */
	public static function jsonClassCodec(
		JsonCodecInterface $codec,
		ContainerInterface $serviceContainer
	): JsonClassCodec;
}

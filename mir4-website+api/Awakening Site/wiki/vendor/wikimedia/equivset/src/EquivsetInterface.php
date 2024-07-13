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
 */

namespace Wikimedia\Equivset;

use LogicException;

/**
 * Equivset
 */
interface EquivsetInterface {

	/**
	 * Gets the equivset.
	 *
	 * @return array An associative array of equivalent characters.
	 */
	public function all(): array;

	/**
	 * Normalize a string.
	 *
	 * @param string $value The string to normalize against the equivset.
	 *
	 * @return string
	 */
	public function normalize( string $value ): string;

	/**
	 * Determine if the two strings are visually equal.
	 *
	 * @param string $str1 The first string.
	 * @param string $str2 The second string.
	 *
	 * @return bool
	 */
	public function isEqual( string $str1, string $str2 ): bool;

	/**
	 * Determine if an equivalent character exists.
	 *
	 * @param string $key The character that was used.
	 *
	 * @return bool If the character has an equivalent.
	 */
	public function has( string $key ): bool;

	/**
	 * Get an equivalent character.
	 *
	 * @param string $key The character that was used.
	 *
	 * @return string The equivalent character.
	 *
	 * @throws LogicException If character does not exist.
	 */
	public function get( string $key ): string;
}

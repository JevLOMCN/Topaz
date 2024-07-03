<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

namespace MediaWiki\Minerva\Menu\Entries;

/**
 * Model for a menu entry.
 */
interface IMenuEntry {

	/**
	 * Get the menu entry name/identifier
	 * @return string
	 */
	public function getName();

	/**
	 * Get the CSS classes that should be applied to the element
	 * @return string[]
	 */
	public function getCSSClasses(): array;

	/**
	 * Returns the list of components of the menu entry
	 *
	 * Each component is an array of HTML attributes with at least:
	 *  - text  -> text to show
	 *  - href  -> href attribute
	 *  - class -> css class applied to it
	 * @return array
	 */
	public function getComponents(): array;

}

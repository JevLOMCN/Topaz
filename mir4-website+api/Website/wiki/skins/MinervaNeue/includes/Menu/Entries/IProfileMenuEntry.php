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
 * Note: Used by Extension:GrowthExperiments
 */
interface IProfileMenuEntry extends IMenuEntry {
	/**
	 * Default tracking code for clicks on profile menu link
	 *
	 * This tracking code will be prefixed with `menu.`
	 */
	public const DEFAULT_PROFILE_TRACKING_CODE = 'profile';

	/**
	 * Override the href for the profile component for logged in users
	 * Note: the tracking code will be prefixed with `menu.` once it gets rendered
	 * @param string $customURL A new href for profile entry
	 * @param string|null $customLabel A new label for profile entry. Null if you don't want to
	 * override it
	 * @param string|null $trackingCode new tracking code
	 * @return IProfileMenuEntry
	 */
	public function overrideProfileURL(
		$customURL, $customLabel = null, $trackingCode = null
	);
}

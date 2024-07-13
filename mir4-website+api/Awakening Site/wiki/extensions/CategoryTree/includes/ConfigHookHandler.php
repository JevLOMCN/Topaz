<?php
/**
 * © 2006-2008 Daniel Kinzler and others
 *
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
 * @ingroup Extensions
 * @author Daniel Kinzler, brightbyte.de
 */

namespace MediaWiki\Extension\CategoryTree;

use MediaWiki\Hook\MediaWikiServicesHook;
use MediaWiki\MediaWikiServices;

/**
 * Hook handler for manipulating configuration.
 *
 * @note This hook runs before the service container is fully initialized, so it cannot have
 * services injected. For this reason, it needs to be separate from the handlers for other
 * hooks.
 */
class ConfigHookHandler implements MediaWikiServicesHook {

	/**
	 * Adjusts config once MediaWiki is fully initialised
	 * TODO: Don't do this, lazy initialize the config
	 * @param MediaWikiServices $services
	 */
	public function onMediaWikiServices( $services ) {
		global $wgCategoryTreeDefaultOptions, $wgCategoryTreeDefaultMode;
		global $wgCategoryTreeCategoryPageOptions, $wgCategoryTreeCategoryPageMode;
		global $wgCategoryTreeOmitNamespace;

		if ( !isset( $wgCategoryTreeDefaultOptions['mode'] ) ) {
			$wgCategoryTreeDefaultOptions['mode'] = $wgCategoryTreeDefaultMode;
		}

		if ( !isset( $wgCategoryTreeDefaultOptions['hideprefix'] ) ) {
			$wgCategoryTreeDefaultOptions['hideprefix'] = $wgCategoryTreeOmitNamespace;
		}

		if ( !isset( $wgCategoryTreeCategoryPageOptions['mode'] ) ) {
			$wgCategoryTreeCategoryPageOptions['mode'] = $wgCategoryTreeCategoryPageMode;
		}
	}

}

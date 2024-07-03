<?php

namespace MediaWiki\Extension\Notifications;

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

use MediaWiki\ResourceLoader as RL;
use RuntimeException;

/**
 * A sibling of secret special sauce.
 * @see RL\OOUIImageModule for familial resemblence
 */
class ResourceLoaderEchoImageModule extends RL\ImageModule {
	protected function loadFromDefinition() {
		if ( $this->definition === null ) {
			return;
		}

		// Check to make sure icons are set
		if ( !isset( $this->definition['icons'] ) ) {
			throw new RuntimeException( 'Icons must be set.' );
		}

		$images = [];
		foreach ( $this->definition['icons'] as $iconName => $definition ) {
			// FIXME: We also have a 'site' icon which is "magical"
			// and uses witchcraft and should be handled specifically
			if ( isset( $definition[ 'path' ] ) && $definition[ 'path' ] ) {
				// string or array, if array, has both rtl and ltr definitions
				$images[ $iconName ][ 'file' ] = $definition[ 'path' ];
			}
		}

		$this->definition[ 'images' ] = $images;
		$this->definition[ 'selector' ] = '.oo-ui-icon-{name}';

		parent::loadFromDefinition();
	}
}

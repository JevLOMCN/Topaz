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
 *
 * @file
 */

namespace MediaWiki\Minerva;

use MediaWiki\ResourceLoader;

/**
 * Subclass with extra LESS variables for Minerva configuration.
 */
class ResourceLoaderSkinModule extends ResourceLoader\SkinModule {
	/**
	 * @param ResourceLoader\Context $context
	 * @return array LESS variables
	 */
	protected function getLessVars( ResourceLoader\Context $context ) {
		$lessVars = parent::getLessVars( $context );
		$lessVars += [
			'wgMinervaApplyKnownTemplateHacks' =>
				$this->getConfig()->get( 'MinervaApplyKnownTemplateHacks' ),
		];
		return $lessVars;
	}
}

<?php

/**
 * Service Wirings for Vector skin
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
 * @since 1.35
 */

use MediaWiki\MediaWikiServices;
use MediaWiki\Skins\Vector\FeatureManagement\FeatureManagerFactory;

// PHP unit does not understand code coverage for this file
// as the @covers annotation cannot cover a specific file
// This is partly tested in ServiceWiringTest.php
// @codeCoverageIgnoreStart

return [
	'Vector.FeatureManagerFactory' => static function ( MediaWikiServices $services ): FeatureManagerFactory {
		return new FeatureManagerFactory(
			$services->getUserOptionsLookup()
		);
	}
];

// @codeCoverageIgnoreEnd

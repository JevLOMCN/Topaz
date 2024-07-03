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

namespace MediaWiki\Linter;

use DatabaseUpdater;
use MediaWiki\Installer\Hook\LoadExtensionSchemaUpdatesHook;

class SchemaHooks implements LoadExtensionSchemaUpdatesHook {
	/**
	 * @param DatabaseUpdater $updater
	 */
	public function onLoadExtensionSchemaUpdates( $updater ) {
		$dbType = $updater->getDB()->getType();
		$dir = dirname( __DIR__ );
		$updater->addExtensionTable( 'linter',
			"{$dir}/sql/{$dbType}/tables-generated.sql"
		);
		// 1.38
		$updater->addExtensionField( 'linter', 'linter_namespace',
			"{$dir}/sql/{$dbType}/patch-linter-add-namespace.sql"
		);
		// 1.38
		$updater->addExtensionField( 'linter', 'linter_template',
			"{$dir}/sql/{$dbType}/patch-linter-template-tag-fields.sql"
		);
		// 1.40
		$updater->modifyExtensionField( 'linter', 'linter_params',
			"{$dir}/sql/{$dbType}/patch-linter-fix-params-null-definition.sql"
		);
	}
}

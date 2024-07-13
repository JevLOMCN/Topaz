<?php

namespace MediaWiki\Extension\Math\HookHandlers;

use DatabaseUpdater;
use MediaWiki\Installer\Hook\LoadExtensionSchemaUpdatesHook;

/**
 * Hook handler for schema hook
 */
class SchemaHooksHandler implements LoadExtensionSchemaUpdatesHook {

	/**
	 * LoadExtensionSchemaUpdates handler; set up math table on install/upgrade.
	 *
	 * @param DatabaseUpdater $updater
	 */
	public function onLoadExtensionSchemaUpdates( $updater ) {
		$type = $updater->getDB()->getType();
		if ( !in_array( $type, [ 'mysql', 'sqlite', 'postgres' ], true ) ) {
			return;
		}

		foreach ( [ 'mathoid', 'mathlatexml' ] as $mode ) {
			$updater->dropExtensionTable( $mode );
		}
	}
}

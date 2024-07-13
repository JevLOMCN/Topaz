<?php

namespace MediaWiki\Extension\OATHAuth\Hook;

use DatabaseUpdater;
use MediaWiki\Extension\OATHAuth\Maintenance\UpdateForMultipleDevicesSupport;
use MediaWiki\Extension\OATHAuth\Maintenance\UpdateTOTPScratchTokensToArray;
use MediaWiki\Installer\Hook\LoadExtensionSchemaUpdatesHook;

class UpdateTables implements LoadExtensionSchemaUpdatesHook {

	/**
	 * @param DatabaseUpdater $updater
	 */
	public function onLoadExtensionSchemaUpdates( $updater ) {
		$type = $updater->getDB()->getType();
		$baseDir = dirname( __DIR__, 2 );
		$typePath = "$baseDir/sql/$type";

		$updater->addExtensionUpdateOnVirtualDomain(
			[ 'virtual-oathauth', 'addTable', 'oathauth_types', "$typePath/tables-generated.sql", true ]
		);

		// Ensure that the oathauth_users table is up-to-date if it exists, so that the migration
		// from the old schema to the new one can be done properly.
		if ( $updater->tableExists( 'oathauth_users' ) ) {
			switch ( $type ) {
				case 'mysql':
				case 'sqlite':
					// 1.36
					$updater->addExtensionUpdate( [
						'runMaintenance',
						UpdateTOTPScratchTokensToArray::class,
						"$baseDir/maintenance/updateTOTPScratchTokensToArray.php"
					] );
					break;

				case 'postgres':
					// 1.38
					$updater->addExtensionUpdateOnVirtualDomain( [
						'virtual-oathauth',
						'modifyTable',
						'oathauth_users',
						"$typePath/patch-oathauth_users-drop-oathauth_users_id_seq.sql",
						true
					] );
					break;
			}

			// 1.41
			$updater->addExtensionUpdate( [
				'runMaintenance',
				UpdateForMultipleDevicesSupport::class,
				"$baseDir/maintenance/UpdateForMultipleDevicesSupport.php"
			] );
			$updater->addExtensionUpdateOnVirtualDomain( [ 'virtual-oathauth', 'dropTable', 'oathauth_users' ] );
		}

		// add new updates here
	}
}

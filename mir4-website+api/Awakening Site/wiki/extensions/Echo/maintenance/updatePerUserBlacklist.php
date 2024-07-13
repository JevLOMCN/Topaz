<?php
/**
 * Update the Per User Blocklist from Usernames to User Ids.
 *
 * @ingroup Maintenance
 */

use MediaWiki\User\User;

require_once getenv( 'MW_INSTALL_PATH' ) !== false
	? getenv( 'MW_INSTALL_PATH' ) . '/maintenance/Maintenance.php'
	: __DIR__ . '/../../../maintenance/Maintenance.php';

/**
 * Maintenance script that changes the usernames to ids.
 *
 * @ingroup Maintenance
 */
class EchoUpdatePerUserBlacklist extends LoggedUpdateMaintenance {

	public function __construct() {
		parent::__construct();

		$this->addDescription( 'Update echo-notifications-blacklist User Preference from Usernames to Ids' );
		$this->setBatchSize( 100 );
		$this->requireExtension( 'Echo' );
	}

	public function getUpdateKey() {
		return __CLASS__;
	}

	public function doDBUpdates() {
		$dbw = $this->getDB( DB_PRIMARY );
		$dbr = $this->getDB( DB_REPLICA );
		$iterator = new BatchRowIterator(
			$dbr,
			'user_properties',
			[ 'up_user', 'up_property' ],
			$this->getBatchSize()
		);
		$iterator->setFetchColumns( [
			'up_user',
			'up_value'
		] );
		$iterator->addConditions( [
			'up_property' => 'echo-notifications-blacklist'
		] );

		$iterator->setCaller( __METHOD__ );

		$this->output( "Updating Echo Notification Blacklist...\n" );

		$centralIdLookup = $this->getServiceContainer()->getCentralIdLookup();
		$processed = 0;
		foreach ( $iterator as $batch ) {
			foreach ( $batch as $row ) {
				if ( !$row->up_value ) {
					continue;
				}

				$value = explode( "\n", $row->up_value );
				$names = array_filter( $value, static function ( $item ) {
					return !is_numeric( $item );
				} );

				// If all of the values are numeric then the user has already been
				// converted.
				if ( !$names ) {
					continue;
				}

				$user = User::newFromId( $row->up_user );
				$ids = $centralIdLookup->centralIdsFromNames( $names, $user );

				$dbw->update(
					'user_properties',
					[
						'up_value'  => implode( "\n", $ids ),
					],
					[
						'up_user' => $row->up_user,
						'up_property' => 'echo-notifications-blacklist',
					],
					__METHOD__
				);
				$processed += $dbw->affectedRows();
				$this->waitForReplication();
			}

			$this->output( "Updated $processed Users\n" );
		}

		return true;
	}
}

$maintClass = EchoUpdatePerUserBlacklist::class;
require_once RUN_MAINTENANCE_IF_MAIN;

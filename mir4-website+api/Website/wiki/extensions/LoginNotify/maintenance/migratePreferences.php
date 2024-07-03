<?php

namespace LoginNotify\Maintenance;

use BatchRowIterator;
use LoggedUpdateMaintenance;
use MediaWiki\MediaWikiServices;
use MediaWiki\User\User;
use MediaWiki\WikiMap\WikiMap;
use RecursiveIteratorIterator;

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}

require_once "$IP/maintenance/Maintenance.php";

/**
 * Cleans up old preference values
 */
class MigratePreferences extends LoggedUpdateMaintenance {

	// Previously, these constants were used by Hooks to force different per-user defaults
	private const OPTIONS_FAKE_TRUTH = 2;
	private const OPTIONS_FAKE_FALSE = 'fake-false';

	/** @var bool[] */
	private static $mapping = [
		self::OPTIONS_FAKE_FALSE => false,
		self::OPTIONS_FAKE_TRUTH => true,
	];

	public function __construct() {
		parent::__construct();
		$this->addDescription( 'Cleans up old-style preferences used by LoginNotify' );
		$this->setBatchSize( 500 );
	}

	/**
	 * Do the actual work. All child classes will need to implement this.
	 * Return true to log the update as done or false (usually on failure).
	 * @return bool
	 */
	protected function doDBUpdates() {
		$dbr = $this->getDB( DB_REPLICA, 'vslow' );
		$lbFactory = MediaWikiServices::getInstance()->getDBLoadBalancerFactory();

		$iterator = new BatchRowIterator( $dbr,
			[ 'user_properties', 'user' ],
			[ 'up_user', 'up_property' ],
			$this->getBatchSize()
		);
		$iterator->addConditions( [
			'user_id=up_user',
			'up_property' => [
				'echo-subscriptions-web-login-fail',
				'echo-subscriptions-web-login-success',
				'echo-subscriptions-email-login-fail',
				'echo-subscriptions-email-login-success',
			],
			'up_value' => [
				self::OPTIONS_FAKE_TRUTH,
				self::OPTIONS_FAKE_FALSE,
			],
		] );
		$iterator->setFetchColumns( [ '*' ] );
		$iterator->setCaller( __METHOD__ );

		$lastRow = (object)[ 'user_id' => 0 ];
		$optionsToUpdate = [];
		$rows = 0;
		$total = 0;
		$iterator = new RecursiveIteratorIterator( $iterator );
		foreach ( $iterator as $row ) {
			$userId = $row->user_id;
			$option = $row->up_property;
			$value = $row->up_value;

			if ( $userId != $lastRow->user_id ) {
				$rows += $this->updateUser( $lastRow, $optionsToUpdate );
				if ( $rows >= $this->getBatchSize() ) {
					$this->output( "  Updated {$rows} rows up to user ID {$lastRow->user_id}\n" );
					$lbFactory->waitForReplication( [ 'wiki' => WikiMap::getCurrentWikiId() ] );
					$total += $rows;
					$rows = 0;
				}
			}
			if ( isset( self::$mapping[ $value ] ) ) {
				$optionsToUpdate[$option] = self::$mapping[ $value ];
			}
			$lastRow = $row;
		}

		$total += $this->updateUser( $lastRow, $optionsToUpdate );

		$this->output( "{$total} rows updated.\n" );

		return true;
	}

	/**
	 * Update one user's preferences
	 *
	 * @param \stdClass $userRow Row from the user table
	 * @param array &$options Associative array of preference => value
	 * @return int Number of options updated
	 */
	private function updateUser( $userRow, array &$options ) {
		if ( $userRow->user_id && $options ) {
			$user = User::newFromRow( $userRow );
			$userOptionsManager = MediaWikiServices::getInstance()->getUserOptionsManager();
			foreach ( $options as $option => $value ) {
				$userOptionsManager->setOption( $user, $option, $value );
			}
			$userOptionsManager->saveOptions( $user );
		}
		$count = count( $options );
		$options = [];
		return $count;
	}

	/**
	 * Get the update key name to go in the update log table
	 * @return string
	 */
	protected function getUpdateKey() {
		return 'LoginNotify::migratePreferences';
	}
}

$maintClass = MigratePreferences::class;
require_once RUN_MAINTENANCE_IF_MAIN;

<?php
/**
 * Recompute notification counts for all users.
 *
 * @ingroup Maintenance
 */

use MediaWiki\Extension\Notifications\DbFactory;
use MediaWiki\Extension\Notifications\NotifUser;
use MediaWiki\User\User;

require_once getenv( 'MW_INSTALL_PATH' ) !== false
	? getenv( 'MW_INSTALL_PATH' ) . '/maintenance/Maintenance.php'
	: __DIR__ . '/../../../maintenance/Maintenance.php';

/**
 * Maintenance script that recomputes notification counts
 *
 * @ingroup Maintenance
 */
class RecomputeNotifCounts extends Maintenance {

	public function __construct() {
		parent::__construct();

		$this->addDescription( 'Recompute notification counts for all users.' );
		$this->addOption( 'user-ids',
			'Comma-separated list of users to recompute notification counts for', false, true );
		$this->addOption( 'notif-types',
			'Recompute counts for all users who have unread notifications of one of these types (comma-separated)',
			false, true );

		$this->setBatchSize( 500 );

		$this->requireExtension( 'Echo' );
	}

	public function execute() {
		$dbFactory = DbFactory::newFromDefault();
		$dbrEcho = $dbFactory->getEchoDb( DB_REPLICA );

		$userIDs = $this->getOption( 'user-ids' );
		$userIDs = $userIDs ? explode( ',', $userIDs ) : null;
		$notifTypes = $this->getOption( 'notif-types' );
		$notifTypes = $notifTypes ? explode( ',', $notifTypes ) : null;

		if ( $userIDs ) {
			$userIterator = array_chunk( $userIDs, $this->getBatchSize() );
		} elseif ( $notifTypes ) {
			$userIterator = new BatchRowIterator(
				$dbrEcho,
				[ 'echo_event', 'echo_notification' ],
				'notification_user',
				$this->getBatchSize()
			);
			$userIterator->addJoinConditions( [
				'echo_notification' => [ 'INNER JOIN', 'notification_event=event_id' ]
			] );
			$userIterator->addConditions( [
				'event_type' => $notifTypes,
				'notification_read_timestamp' => null
			] );
			$userIterator->addOptions( [
				'GROUP BY' => 'notification_user'
			] );
			$userIterator->setCaller( __METHOD__ );
		} else {
			$userQuery = User::getQueryInfo();
			$userIterator = new BatchRowIterator(
				$this->getReplicaDB(), $userQuery['tables'], 'user_id', $this->getBatchSize()
			);
			$userIterator->setFetchColumns( $userQuery['fields'] );
			$userIterator->addJoinConditions( $userQuery['joins'] );
			$userIterator->setCaller( __METHOD__ );
		}

		$count = 0;
		foreach ( $userIterator as $batch ) {
			foreach ( $batch as $rowOrID ) {
				if ( is_object( $rowOrID ) && isset( $rowOrID->user_id ) ) {
					$user = User::newFromRow( $rowOrID );
				} else {
					$user = User::newFromId( is_object( $rowOrID ) ? $rowOrID->notification_user : $rowOrID );
				}
				$notifUser = NotifUser::newFromUser( $user );
				$notifUser->resetNotificationCount();
			}
			$count += count( $batch );
			$this->output( "$count users' counts recomputed.\n" );
			$this->waitForReplication();
		}
	}
}

$maintClass = RecomputeNotifCounts::class;
require_once RUN_MAINTENANCE_IF_MAIN;

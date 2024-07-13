<?php

use MediaWiki\Extension\Notifications\AttributeManager;
use MediaWiki\Extension\Notifications\DbFactory;
use MediaWiki\Extension\Notifications\NotifUser;
use MediaWiki\Extension\Notifications\UnreadWikis;
use MediaWiki\User\CentralId\CentralIdLookup;
use MediaWiki\User\User;
use MediaWiki\WikiMap\WikiMap;

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}
require_once "$IP/maintenance/Maintenance.php";

class BackfillUnreadWikis extends Maintenance {
	public function __construct() {
		parent::__construct();

		$this->addDescription( "Backfill echo_unread_wikis table" );
		$this->addOption( 'rebuild', 'Only recompute already-existing rows' );
		$this->setBatchSize( 300 );
		$this->requireExtension( 'Echo' );
	}

	public function execute() {
		$dbFactory = DbFactory::newFromDefault();
		$lookup = $this->getServiceContainer()->getCentralIdLookup();

		$rebuild = $this->hasOption( 'rebuild' );
		if ( $rebuild ) {
			$iterator = new BatchRowIterator(
				$dbFactory->getSharedDb( DB_REPLICA ),
				'echo_unread_wikis',
				'euw_user',
				$this->getBatchSize()
			);
			$iterator->addConditions( [ 'euw_wiki' => WikiMap::getCurrentWikiId() ] );
		} else {
			$userQuery = User::getQueryInfo();
			$iterator = new BatchRowIterator(
				$this->getReplicaDB(), $userQuery['tables'], 'user_id', $this->getBatchSize()
			);
			$iterator->setFetchColumns( $userQuery['fields'] );
			$iterator->addJoinConditions( $userQuery['joins'] );
		}

		$iterator->setCaller( __METHOD__ );

		$processed = 0;
		foreach ( $iterator as $batch ) {
			foreach ( $batch as $row ) {
				if ( $rebuild ) {
					$user = $lookup->localUserFromCentralId(
						$row->euw_user,
						CentralIdLookup::AUDIENCE_RAW
					);
					if ( !$user ) {
						continue;
					}
				} else {
					$user = User::newFromRow( $row );
				}

				$notifUser = NotifUser::newFromUser( $user );
				$uw = UnreadWikis::newFromUser( $user );
				if ( $uw ) {
					$alertCount = $notifUser->getNotificationCount( AttributeManager::ALERT, false );
					$alertUnread = $notifUser->getLastUnreadNotificationTime( AttributeManager::ALERT, false );

					$msgCount = $notifUser->getNotificationCount( AttributeManager::MESSAGE, false );
					$msgUnread = $notifUser->getLastUnreadNotificationTime( AttributeManager::MESSAGE, false );

					if ( ( $alertCount !== 0 && $alertUnread === false ) ||
						( $msgCount !== 0 && $msgUnread === false )
					) {
						// If there are alerts, there should be an alert timestamp (same for messages).

						// Otherwise, there is a race condition between the two values, indicating there's already
						// just been an updateCount call, so we can skip this user.
						continue;
					}

					$uw->updateCount( WikiMap::getCurrentWikiId(), $alertCount, $alertUnread, $msgCount, $msgUnread );
				}
			}

			$processed += count( $batch );
			$this->output( "Updated $processed users.\n" );
			$this->waitForReplication();
		}
	}
}

$maintClass = BackfillUnreadWikis::class;
require_once RUN_MAINTENANCE_IF_MAIN;

<?php

namespace MediaWiki\Extension\Notifications\Controller;

use MediaWiki\Deferred\DeferredUpdates;
use MediaWiki\Extension\Notifications\Mapper\EventMapper;
use MediaWiki\Extension\Notifications\Mapper\NotificationMapper;
use MediaWiki\Extension\Notifications\NotifUser;
use MediaWiki\MediaWikiServices;
use MediaWiki\User\User;

/**
 * This class represents the controller for moderating notifications
 */
class ModerationController {

	/**
	 * Moderate or unmoderate events
	 *
	 * @param int[] $eventIds
	 * @param bool $moderate Whether to moderate or unmoderate the events
	 */
	public static function moderate( array $eventIds, $moderate ) {
		if ( !$eventIds ) {
			return;
		}

		$eventMapper = new EventMapper();
		$notificationMapper = new NotificationMapper();

		$affectedUserIds = $notificationMapper->fetchUsersWithNotificationsForEvents( $eventIds );
		$eventMapper->toggleDeleted( $eventIds, $moderate );

		$fname = __METHOD__;

		DeferredUpdates::addCallableUpdate( static function () use ( $affectedUserIds, $fname ) {
			// This update runs after the main transaction round commits.
			// Wait for the event deletions to be propagated to replica DBs
			$lbFactory = MediaWikiServices::getInstance()->getDBLoadBalancerFactory();
			$lbFactory->waitForReplication( [ 'timeout' => 5 ] );
			$lbFactory->flushReplicaSnapshots( $fname );
			// Recompute the notification count for the
			// users whose notifications have been moderated.
			foreach ( $affectedUserIds as $userId ) {
				$user = User::newFromId( $userId );
				NotifUser::newFromUser( $user )->resetNotificationCount();
			}
		} );
	}
}

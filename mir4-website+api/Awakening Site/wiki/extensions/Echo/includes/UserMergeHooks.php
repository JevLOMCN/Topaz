<?php

namespace MediaWiki\Extension\Notifications;

use MediaWiki\Deferred\DeferredUpdates;
use MediaWiki\Extension\Notifications\Model\Event;
use MediaWiki\Extension\UserMerge\Hooks\AccountDeleteTablesHook;
use MediaWiki\Extension\UserMerge\Hooks\AccountFieldsHook;
use MediaWiki\Extension\UserMerge\Hooks\MergeAccountFromToHook;
use MediaWiki\User\User;

class UserMergeHooks implements
	AccountFieldsHook,
	MergeAccountFromToHook,
	AccountDeleteTablesHook
{

	/**
	 * For integration with the UserMerge extension.
	 *
	 * @param array &$updateFields
	 */
	public function onUserMergeAccountFields( array &$updateFields ) {
		// [ tableName, idField, textField ]
		$dbw = DbFactory::newFromDefault()->getEchoDb( DB_PRIMARY );
		$updateFields[] = [ 'echo_event', 'event_agent_id', 'db' => $dbw ];
		$updateFields[] = [ 'echo_notification', 'notification_user', 'db' => $dbw, 'options' => [ 'IGNORE' ] ];
		$updateFields[] = [ 'echo_email_batch', 'eeb_user_id', 'db' => $dbw, 'options' => [ 'IGNORE' ] ];
	}

	public function onMergeAccountFromTo( User &$oldUser, User &$newUser ) {
		$method = __METHOD__;
		DeferredUpdates::addCallableUpdate( static function () use ( $oldUser, $newUser, $method ) {
			if ( $newUser->isRegistered() ) {
				// Select notifications that are now sent to the same user
				$dbw = DbFactory::newFromDefault()->getEchoDb( DB_PRIMARY );
				$attributeManager = Services::getInstance()->getAttributeManager();
				$selfIds = $dbw->selectFieldValues(
					[ 'echo_notification', 'echo_event' ],
					'event_id',
					[
						'notification_user' => $newUser->getId(),
						'notification_event = event_id',
						'notification_user = event_agent_id',
						'event_type NOT IN (' . $dbw->makeList( $attributeManager->getNotifyAgentEvents() ) . ')'
					],
					$method
				) ?: [];

				// Select newer welcome notification(s)
				$welcomeIds = $dbw->selectFieldValues(
					[ 'echo_notification', 'echo_event' ],
					'event_id',
					[
						'notification_user' => $newUser->getId(),
						'notification_event = event_id',
						'event_type' => 'welcome',
					],
					$method,
					[
						'ORDER BY' => 'notification_timestamp ASC',
						'OFFSET' => 1,
					]
				) ?: [];

				// Select newer milestone notifications (per milestone level)
				$counts = [];
				$thankYouIds = [];
				$thankYouRows = $dbw->select(
					[ 'echo_notification', 'echo_event' ],
					Event::selectFields(),
					[
						'notification_user' => $newUser->getId(),
						'notification_event = event_id',
						'event_type' => 'thank-you-edit',
					],
					$method,
					[ 'ORDER BY' => 'notification_timestamp ASC' ]
				) ?: [];
				foreach ( $thankYouRows as $row ) {
					$event = Event::newFromRow( $row );
					$editCount = $event ? $event->getExtraParam( 'editCount' ) : null;
					if ( $editCount ) {
						if ( isset( $counts[$editCount] ) ) {
							$thankYouIds[] = $row->event_id;
						} else {
							$counts[$editCount] = true;
						}
					}
				}

				// Delete notifications
				$ids = array_merge( $selfIds, $welcomeIds, $thankYouIds );
				if ( $ids !== [] ) {
					$dbw->delete(
						'echo_notification',
						[
							'notification_user' => $newUser->getId(),
							'notification_event' => $ids
						],
						$method
					);
				}
			}

			NotifUser::newFromUser( $oldUser )->resetNotificationCount();
			if ( $newUser->isRegistered() ) {
				NotifUser::newFromUser( $newUser )->resetNotificationCount();
			}
		} );
	}

	public function onUserMergeAccountDeleteTables( array &$tables ) {
		$dbw = DbFactory::newFromDefault()->getEchoDb( DB_PRIMARY );
		$tables['echo_notification'] = [ 'notification_user', 'db' => $dbw ];
		$tables['echo_email_batch'] = [ 'eeb_user_id', 'db' => $dbw ];
	}
}

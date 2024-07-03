<?php

namespace MediaWiki\Extension\Notifications\Mapper;

use InvalidArgumentException;
use MediaWiki\Extension\Notifications\Model\Event;
use MediaWiki\User\User;

/**
 * Database mapper for Event model, which is an immutable class, there should
 * not be any update to it
 */
class EventMapper extends AbstractMapper {

	/**
	 * Insert an event record
	 *
	 * @param Event $event
	 * @return int
	 */
	public function insert( Event $event ) {
		$dbw = $this->dbFactory->getEchoDb( DB_PRIMARY );

		$row = $event->toDbArray();

		$dbw->insert( 'echo_event', $row, __METHOD__ );

		$id = $dbw->insertId();

		$listeners = $this->getMethodListeners( __FUNCTION__ );
		foreach ( $listeners as $listener ) {
			$dbw->onTransactionCommitOrIdle( $listener, __METHOD__ );
		}

		return $id;
	}

	/**
	 * Create an Event by id
	 *
	 * @param int $id
	 * @param bool $fromPrimary
	 * @return Event|false False if it wouldn't load/unserialize
	 */
	public function fetchById( $id, $fromPrimary = false ) {
		$db = $fromPrimary ? $this->dbFactory->getEchoDb( DB_PRIMARY ) : $this->dbFactory->getEchoDb( DB_REPLICA );

		$row = $db->selectRow( 'echo_event', Event::selectFields(), [ 'event_id' => $id ], __METHOD__ );

		// If the row was not found, fall back on the primary database if it makes sense to do so
		if ( !$row && !$fromPrimary && $this->dbFactory->canRetryPrimary() ) {
			return $this->fetchById( $id, true );
		} elseif ( !$row ) {
			throw new InvalidArgumentException( "No Event found with ID: $id" );
		}

		return Event::newFromRow( $row );
	}

	/**
	 * @param int[] $eventIds
	 * @param bool $deleted
	 */
	public function toggleDeleted( array $eventIds, $deleted ) {
		$dbw = $this->dbFactory->getEchoDb( DB_PRIMARY );

		$selectDeleted = $deleted ? 0 : 1;
		$setDeleted = $deleted ? 1 : 0;
		$dbw->update(
			'echo_event',
			[
				'event_deleted' => $setDeleted,
			],
			[
				'event_deleted' => $selectDeleted,
				'event_id' => $eventIds,
			],
			__METHOD__
		);
	}

	/**
	 * Fetch events associated with a page
	 *
	 * @param int $pageId
	 * @return Event[] Events
	 */
	public function fetchByPage( $pageId ) {
		$events = [];
		$seenEventIds = [];
		$dbr = $this->dbFactory->getEchoDb( DB_REPLICA );

		// From echo_event
		$res = $dbr->select(
			[ 'echo_event' ],
			Event::selectFields(),
			[ 'event_page_id' => $pageId ],
			__METHOD__
		);
		if ( $res ) {
			foreach ( $res as $row ) {
				$event = Event::newFromRow( $row );
				$events[] = $event;
				$seenEventIds[] = $event->getId();
			}
		}

		// From echo_target_page
		$conds = [ 'etp_page' => $pageId ];
		if ( $seenEventIds ) {
			// Some events have both a title and target page(s).
			// Skip the events that were already found in the echo_event table (the query above).
			$conds[] = 'event_id NOT IN ( ' . $dbr->makeList( $seenEventIds ) . ' )';
		}
		$res = $dbr->select(
			[ 'echo_event', 'echo_target_page' ],
			Event::selectFields(),
			$conds,
			__METHOD__,
			[ 'DISTINCT' ],
			[ 'echo_target_page' => [ 'INNER JOIN', 'event_id=etp_event' ] ]
		);
		if ( $res ) {
			foreach ( $res as $row ) {
				$events[] = Event::newFromRow( $row );
			}
		}

		return $events;
	}

	/**
	 * Fetch event IDs associated with a page
	 *
	 * @param int $pageId
	 * @return int[] Event IDs
	 */
	public function fetchIdsByPage( $pageId ) {
		$events = $this->fetchByPage( $pageId );
		$eventIds = array_map(
			static function ( Event $event ) {
				return $event->getId();
			},
			$events
		);
		return $eventIds;
	}

	/**
	 * Fetch events unread by a user and associated with a page
	 *
	 * @param User $user
	 * @param int $pageId
	 * @return Event[]
	 */
	public function fetchUnreadByUserAndPage( User $user, $pageId ) {
		$dbr = $this->dbFactory->getEchoDb( DB_REPLICA );
		$fields = array_merge( Event::selectFields(), [ 'notification_timestamp' ] );

		$res = $dbr->select(
			[ 'echo_event', 'echo_notification', 'echo_target_page' ],
			$fields,
			[
				'event_deleted' => 0,
				'notification_user' => $user->getId(),
				'notification_read_timestamp' => null,
				'etp_page' => $pageId,
			],
			__METHOD__,
			[],
			[
				'echo_target_page' => [ 'INNER JOIN', 'etp_event=event_id' ],
				'echo_notification' => [ 'INNER JOIN', [ 'notification_event=event_id' ] ],
			]
		);

		$data = [];
		foreach ( $res as $row ) {
			$data[] = Event::newFromRow( $row );
		}

		return $data;
	}

	/**
	 * Find out which of the given event IDs are orphaned, and delete them.
	 *
	 * An event is orphaned if it is not referred to by any rows in the echo_notification or
	 * echo_email_batch tables. If $ignoreUserId is set, rows for that user are not considered when
	 * determining orphanhood; if $ignoreUserTable is set, this only applies to that table.
	 * Use this when you've just recently deleted rows related to this user on the primary database, so that
	 * this function won't refuse to delete recently-orphaned events because it still sees the
	 * recently-deleted rows on the replica.
	 *
	 * @param array $eventIds Event IDs to check to see if they have become orphaned
	 * @param int|null $ignoreUserId Allow events to be deleted if the only referring rows
	 *  have this user ID
	 * @param string|null $ignoreUserTable Restrict $ignoreUserId to this table only
	 *  ('echo_notification' or 'echo_email_batch')
	 */
	public function deleteOrphanedEvents( array $eventIds, $ignoreUserId = null, $ignoreUserTable = null ) {
		$dbw = $this->dbFactory->getEchoDb( DB_PRIMARY );
		$dbr = $this->dbFactory->getEchoDb( DB_REPLICA );

		$notifJoinConds = [];
		$emailJoinConds = [];
		if ( $ignoreUserId !== null ) {
			if ( $ignoreUserTable === null || $ignoreUserTable === 'echo_notification' ) {
				$notifJoinConds[] = 'notification_user != ' . $dbr->addQuotes( $ignoreUserId );
			}
			if ( $ignoreUserTable === null || $ignoreUserTable === 'echo_email_batch' ) {
				$emailJoinConds[] = 'eeb_user_id != ' . $dbr->addQuotes( $ignoreUserId );
			}
		}
		$orphanedEventIds = $dbr->selectFieldValues(
			[ 'echo_event', 'echo_notification', 'echo_email_batch' ],
			'event_id',
			[
				'event_id' => $eventIds,
				'notification_timestamp' => null,
				'eeb_user_id' => null
			],
			__METHOD__,
			[],
			[
				'echo_notification' => [ 'LEFT JOIN', array_merge( [
					'notification_event=event_id'
				], $notifJoinConds ) ],
				'echo_email_batch' => [ 'LEFT JOIN', array_merge( [
					'eeb_event_id=event_id'
				], $emailJoinConds ) ]
			]
		);
		if ( $orphanedEventIds ) {
			$dbw->delete( 'echo_event', [ 'event_id' => $orphanedEventIds ], __METHOD__ );
			$dbw->delete( 'echo_target_page', [ 'etp_event' => $orphanedEventIds ], __METHOD__ );
		}
	}

}

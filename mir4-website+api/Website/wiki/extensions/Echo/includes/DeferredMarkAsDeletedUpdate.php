<?php

namespace MediaWiki\Extension\Notifications;

use MediaWiki\Deferred\DeferrableUpdate;
use MediaWiki\Deferred\DeferredUpdates;
use MediaWiki\Extension\Notifications\Controller\ModerationController;
use MediaWiki\Extension\Notifications\Model\Event;
use MediaWiki\Logger\LoggerFactory;

/**
 * Mark event notifications as deleted at the end of a request.  Used to queue up
 * individual events to mark due to formatting failures.
 */
class DeferredMarkAsDeletedUpdate implements DeferrableUpdate {
	/**
	 * @var Event[]
	 */
	protected $events = [];

	/**
	 * @param Event $event
	 */
	public static function add( Event $event ) {
		static $update;
		if ( $update === null ) {
			$update = new self();
			DeferredUpdates::addUpdate( $update );
		}
		$update->addInternal( $event );
	}

	/**
	 * @param Event $event
	 */
	private function addInternal( Event $event ) {
		$this->events[] = $event;
	}

	private function filterEventsWithTitleDbLag() {
		return array_filter(
			$this->events,
			static function ( Event $event ) {
				if ( !$event->getTitle() && $event->getTitle( true ) ) {
					// It is very likely this event was found
					// unrenderable because of replica lag.
					// Do not moderate it at this time.
					LoggerFactory::getInstance( 'Echo' )->debug(
						'DeferredMarkAsDeletedUpdate: Event {eventId} was found unrenderable' .
							' but its associated title exists on primary database. Skipping.',
						[
							'eventId' => $event->getId(),
							'title' => $event->getTitle()->getPrefixedText(),
						]
					);
					return false;
				}
				return true;
			}
		);
	}

	/**
	 * Marks all queued notifications as read.
	 * Satisfies DeferrableUpdate interface
	 */
	public function doUpdate() {
		$events = $this->filterEventsWithTitleDbLag();

		$eventIds = array_map(
			static function ( Event $event ) {
				return $event->getId();
			},
			$events
		);

		ModerationController::moderate( $eventIds, true );
		$this->events = [];
	}
}

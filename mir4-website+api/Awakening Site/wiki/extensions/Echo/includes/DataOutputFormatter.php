<?php

namespace MediaWiki\Extension\Notifications;

use Language;
use MediaWiki\Extension\Notifications\Formatters\EchoEventFormatter;
use MediaWiki\Extension\Notifications\Formatters\EchoFlyoutFormatter;
use MediaWiki\Extension\Notifications\Formatters\EchoModelFormatter;
use MediaWiki\Extension\Notifications\Formatters\SpecialNotificationsFormatter;
use MediaWiki\Extension\Notifications\Model\Event;
use MediaWiki\Extension\Notifications\Model\Notification;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\User\User;
use MediaWiki\Utils\MWTimestamp;
use MediaWiki\WikiMap\WikiMap;
use RequestContext;

/**
 * Utility class that formats a notification in the format specified
 * @todo Make this a service with DI
 */
class DataOutputFormatter {

	/**
	 * @var string[] type => class
	 */
	protected static $formatters = [
		'flyout' => EchoFlyoutFormatter::class,
		'model' => EchoModelFormatter::class,
		'special' => SpecialNotificationsFormatter::class,
		'html' => SpecialNotificationsFormatter::class,
	];

	/**
	 * Format a notification for a user in the format specified
	 *
	 * This method returns an array of data, some of it html
	 * escaped, some of it not. This confuses phan-taint-check,
	 * so mark it as safe for html and safe to be escaped again.
	 * @return-taint onlysafefor_htmlnoent
	 *
	 * @param Notification $notification
	 * @param string|false $format Output format, false to not format any notifications
	 * @param User $user the target user viewing the notification
	 * @param Language $lang Language to format the notification in
	 * @return array|false False if it could not be formatted
	 */
	public static function formatOutput(
		Notification $notification,
		$format,
		User $user,
		Language $lang
	) {
		$event = $notification->getEvent();
		$timestamp = $notification->getTimestamp();
		$utcTimestampIso8601 = wfTimestamp( TS_ISO_8601, $timestamp );
		$utcTimestampUnix = (int)wfTimestamp( TS_UNIX, $timestamp );
		$utcTimestampMW = wfTimestamp( TS_MW, $timestamp );
		$bundledIds = null;

		$bundledNotifs = $notification->getBundledNotifications();
		if ( $bundledNotifs ) {
			$bundledEvents = array_map( static function ( Notification $notif ) {
				return $notif->getEvent();
			}, $bundledNotifs );
			$event->setBundledEvents( $bundledEvents );

			$bundledIds = array_map( static function ( $event ) {
				return (int)$event->getId();
			}, $bundledEvents );
		}

		$timestampMw = self::getUserLocalTime( $user, $timestamp );

		// Start creating date section header
		$now = (int)wfTimestamp();
		$dateFormat = substr( $timestampMw, 0, 8 );
		$timeDiff = $now - $utcTimestampUnix;
		// Most notifications would be more than two days ago, check this
		// first instead of checking 'today' then 'yesterday'
		if ( $timeDiff > 172800 ) {
			$date = self::getDateHeader( $user, $timestampMw );
		// 'Today'
		} elseif ( substr( self::getUserLocalTime( $user, $now ), 0, 8 ) === $dateFormat ) {
			$date = wfMessage( 'echo-date-today' )->escaped();
		// 'Yesterday'
		} elseif ( substr( self::getUserLocalTime( $user, $now - 86400 ), 0, 8 ) === $dateFormat ) {
			$date = wfMessage( 'echo-date-yesterday' )->escaped();
		} else {
			$date = self::getDateHeader( $user, $timestampMw );
		}
		// End creating date section header

		$output = [
			'wiki' => WikiMap::getCurrentWikiId(),
			'id' => $event->getId(),
			'type' => $event->getType(),
			'category' => $event->getCategory(),
			'section' => $event->getSection(),
			'timestamp' => [
				// ISO 8601 is supposed to be the *only* format used for
				// date output, but back-compat...
				'utciso8601' => $utcTimestampIso8601,

				// UTC timestamp in UNIX format used for loading more notification
				'utcunix' => $utcTimestampUnix,
				'unix' => self::getUserLocalTime( $user, $timestamp, TS_UNIX ),
				'utcmw' => $utcTimestampMW,
				'mw' => $timestampMw,
				'date' => $date
			],
		];

		if ( $bundledIds ) {
			$output['bundledIds'] = $bundledIds;
		}

		if ( $event->getVariant() ) {
			$output['variant'] = $event->getVariant();
		}

		$title = $event->getTitle();
		if ( $title ) {
			$output['title'] = [
				'full' => $title->getPrefixedText(),
				'namespace' => $title->getNsText(),
				'namespace-key' => $title->getNamespace(),
				'text' => $title->getText(),
			];
		}

		$agent = $event->getAgent();
		if ( $agent ) {
			if ( $event->userCan( RevisionRecord::DELETED_USER, $user ) ) {
				$output['agent'] = [
					'id' => $agent->getId(),
					'name' => $agent->getName(),
				];
			} else {
				$output['agent'] = [ 'userhidden' => '' ];
			}
		}

		if ( $event->getRevision() ) {
			$output['revid'] = $event->getRevision()->getId();
		}

		if ( $notification->getReadTimestamp() ) {
			$output['read'] = $notification->getReadTimestamp();
		}

		// This is only meant for unread notifications, if a notification has a target
		// page, then it shouldn't be auto marked as read unless the user visits
		// the target page or a user marks it as read manually ( coming soon )
		$output['targetpages'] = [];
		if ( $notification->getTargetPages() ) {
			foreach ( $notification->getTargetPages() as $targetPage ) {
				$output['targetpages'][] = $targetPage->getPageId();
			}
		}

		if ( $format ) {
			$formatted = self::formatNotification( $event, $user, $format, $lang );
			if ( $formatted === false ) {
				// Can't display it, so mark it as read
				DeferredMarkAsDeletedUpdate::add( $event );
				return false;
			}
			$output['*'] = $formatted;

			if ( $notification->getBundledNotifications() &&
				Services::getInstance()->getAttributeManager()->isBundleExpandable( $event->getType() )
			) {
				$output['bundledNotifications'] = array_values( array_filter( array_map(
					static function ( Notification $notification ) use ( $format, $user, $lang ) {
						// remove nested notifications to
						// - ensure they are formatted as single notifications (not bundled)
						// - prevent further re-entrance on the current notification
						$notification->setBundledNotifications( [] );
						$notification->getEvent()->setBundledEvents( [] );
						return self::formatOutput( $notification, $format, $user, $lang );
					},
					array_merge( [ $notification ], $notification->getBundledNotifications() )
				) ) );
			}
		}

		return $output;
	}

	/**
	 * @param Event $event
	 * @param User $user
	 * @param string $format
	 * @param Language $lang
	 * @return string[]|string|false False if it could not be formatted
	 */
	protected static function formatNotification( Event $event, User $user, $format, $lang ) {
		if ( isset( self::$formatters[$format] ) ) {
			$class = self::$formatters[$format];
			/** @var EchoEventFormatter $formatter */
			$formatter = new $class( $user, $lang );
			return $formatter->format( $event );
		}

		return false;
	}

	/**
	 * Get the date header in user's format, 'May 10' or '10 May', depending
	 * on user's date format preference
	 * @param User $user
	 * @param string $timestampMw
	 * @return string
	 */
	protected static function getDateHeader( User $user, $timestampMw ) {
		$lang = RequestContext::getMain()->getLanguage();
		$dateFormat = $lang->getDateFormatString( 'pretty', $user->getDatePreference() ?: 'default' );

		return $lang->sprintfDate( $dateFormat, $timestampMw );
	}

	/**
	 * Helper function for converting UTC timezone to a user's timezone
	 *
	 * @param User $user
	 * @param string|int $ts
	 * @param int $format output format
	 *
	 * @return string
	 */
	public static function getUserLocalTime( User $user, $ts, $format = TS_MW ) {
		$timestamp = new MWTimestamp( $ts );
		$timestamp->offsetForUser( $user );

		return $timestamp->getTimestamp( $format );
	}

}

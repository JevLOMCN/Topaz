<?php

namespace MediaWiki\Extension\Notifications;

use BatchRowIterator;
use Language;
use MailAddress;
use MediaWiki\Extension\Notifications\Formatters\EchoHtmlDigestEmailFormatter;
use MediaWiki\Extension\Notifications\Formatters\EchoPlainTextDigestEmailFormatter;
use MediaWiki\Extension\Notifications\Mapper\EventMapper;
use MediaWiki\Extension\Notifications\Model\Event;
use MediaWiki\Languages\LanguageFactory;
use MediaWiki\MediaWikiServices;
use MediaWiki\User\Options\UserOptionsManager;
use MediaWiki\User\User;
use stdClass;
use UserMailer;
use Wikimedia\Rdbms\IResultWrapper;

/**
 * Handle user email batch ( daily/ weekly )
 */
class EmailBatch {

	/**
	 * @var User the user to be notified
	 */
	protected $mUser;

	/**
	 * @var Language
	 */
	protected $language;

	/**
	 * @var UserOptionsManager
	 */
	protected $userOptionsManager;

	/**
	 * @var Event[] events included in this email
	 */
	protected $events = [];

	/**
	 * @var Event the last notification event of this batch
	 */
	protected $lastEvent;

	/**
	 * @var int the event count, this count is supported up to self::$displaySize + 1
	 */
	protected $count = 0;

	/**
	 * @var int number of bundle events to include in an email,
	 * we cannot include all events in a batch email
	 */
	protected static $displaySize = 20;

	/**
	 * @param User $user
	 * @param UserOptionsManager $userOptionsManager
	 * @param LanguageFactory $languageFactory
	 */
	public function __construct(
		User $user,
		UserOptionsManager $userOptionsManager,
		LanguageFactory $languageFactory
	) {
		$this->mUser = $user;
		$this->language = $languageFactory->getLanguage(
			$userOptionsManager->getOption( $user, 'language' )
		);
		$this->userOptionsManager = $userOptionsManager;
	}

	/**
	 * Factory method to determine whether to create a batch instance for this
	 * user based on the user setting, this assumes the following value for
	 * member setting for echo-email-frequency
	 * -1 - no email
	 *  0 - instant
	 *  1 - once everyday
	 *  7 - once every 7 days
	 * @param int $userId
	 * @param bool $enforceFrequency Whether email sending frequency should
	 *  be enforced.
	 *
	 *  When true, today's notifications won't be returned if they are
	 *  configured to go out tonight or at the end of the week.
	 *
	 *  When false, all pending notifications will be returned.
	 * @return EmailBatch|false
	 */
	public static function newFromUserId( $userId, $enforceFrequency = true ) {
		$user = User::newFromId( (int)$userId );
		$services = MediaWikiServices::getInstance();
		$userOptionsManager = $services->getUserOptionsManager();
		$languageFactory = $services->getLanguageFactory();

		$userEmailSetting = (int)$userOptionsManager->getOption( $user, 'echo-email-frequency' );

		// clear all existing events if user decides not to receive emails
		if ( $userEmailSetting == -1 ) {
			$emailBatch = new self( $user, $userOptionsManager, $languageFactory );
			$emailBatch->clearProcessedEvent();

			return false;
		}

		// @Todo - There may be some items idling in the queue, eg, a bundle job is lost
		// and there is not never another message with the same hash or a user switches from
		// digest to instant.  We should check the first item in the queue, if it doesn't
		// have either web or email bundling or created long ago, then clear it, this will
		// prevent idling item queuing up.

		// user has instant email delivery
		if ( $userEmailSetting == 0 ) {
			return false;
		}

		$userLastBatch = $userOptionsManager->getOption( $user, 'echo-email-last-batch' );

		// send email batch, if
		// 1. it has been long enough since last email batch based on frequency
		// 2. there is no last batch timestamp recorded for the user
		// 3. user has switched from batch to instant email, send events left in the queue
		if ( $userLastBatch ) {
			// use 20 as hours per day to get estimate
			$nextBatch = (int)wfTimestamp( TS_UNIX, $userLastBatch ) + $userEmailSetting * 20 * 60 * 60;
			if ( $enforceFrequency && wfTimestamp( TS_MW, $nextBatch ) > wfTimestampNow() ) {
				return false;
			}
		}

		return new self( $user, $userOptionsManager, $languageFactory );
	}

	/**
	 * Wrapper function that calls other functions required to process email batch
	 */
	public function process() {
		// if there is no event for this user, exist the process
		if ( !$this->setLastEvent() ) {
			return;
		}

		// get valid events
		$events = $this->getEvents();

		if ( $events ) {
			foreach ( $events as $row ) {
				$this->count++;
				if ( $this->count > self::$displaySize ) {
					break;
				}
				$event = Event::newFromRow( $row );
				if ( !$event ) {
					continue;
				}
				$event->setBundleHash( $row->eeb_event_hash );
				$this->events[] = $event;
			}

			$bundler = new Bundler();
			$this->events = $bundler->bundle( $this->events );

			$this->sendEmail();
		}

		$this->clearProcessedEvent();
		$this->updateUserLastBatchTimestamp();
	}

	/**
	 * Set the last event of this batch, this is a cutoff point for clearing
	 * processed/invalid events
	 *
	 * @return bool true if event exists false otherwise
	 */
	protected function setLastEvent() {
		$dbr = DbFactory::newFromDefault()->getEchoDb( DB_REPLICA );
		$res = $dbr->selectField(
			[ 'echo_email_batch' ],
			'MAX( eeb_event_id )',
			[ 'eeb_user_id' => $this->mUser->getId() ],
			__METHOD__
		);

		if ( $res ) {
			$this->lastEvent = $res;

			return true;
		}

		return false;
	}

	/**
	 * Update the user's last batch timestamp after a successful batch
	 */
	protected function updateUserLastBatchTimestamp() {
		$this->userOptionsManager->setOption(
			$this->mUser,
			'echo-email-last-batch',
			wfTimestampNow()
		);
		$this->mUser->saveSettings();
		$this->mUser->invalidateCache();
	}

	/**
	 * Get the events queued for the current user
	 * @return stdClass[]
	 */
	protected function getEvents() {
		global $wgEchoNotifications;

		$events = [];

		$validEvents = array_keys( $wgEchoNotifications );

		// Per the tech discussion in the design meeting (03/22/2013), since this is
		// processed by a cron job, it's okay to use GROUP BY over more complex
		// composite index, favor insert performance, storage space over read
		// performance in this case
		if ( $validEvents ) {
			$dbr = DbFactory::newFromDefault()->getEchoDb( DB_REPLICA );

			$conds = [
				'eeb_user_id' => $this->mUser->getId(),
				'event_id = eeb_event_id',
				'event_type' => $validEvents
			];

			$tables = [ 'echo_email_batch', 'echo_event' ];
			if ( $this->userOptionsManager->getOption(
				$this->mUser, 'echo-dont-email-read-notifications'
			) ) {
				$conds = array_merge(
					$conds,
					[
						'notification_event = event_id',
						'notification_read_timestamp IS NULL',
					]
				);
				$tables[] = 'echo_notification';
			}

			// See setLastEvent() for more detail for this variable
			if ( $this->lastEvent ) {
				$conds[] = 'eeb_event_id <= ' . (int)$this->lastEvent;
			}
			$fields = array_merge( Event::selectFields(), [
				'eeb_id',
				'eeb_user_id',
				'eeb_event_priority',
				'eeb_event_id',
				'eeb_event_hash',
			] );

			$res = $dbr->select(
				$tables,
				$fields,
				$conds,
				__METHOD__,
				[
					'ORDER BY' => 'eeb_event_priority',
					'LIMIT' => self::$displaySize + 1,
				]
			);

			foreach ( $res as $row ) {
				// records in the queue inserted before email bundling code
				// have no hash, in this case, we just ignore them
				if ( $row->eeb_event_hash ) {
					$events[$row->eeb_id] = $row;
				}
			}
		}

		return $events;
	}

	/**
	 * Clear "processed" events in the queue,
	 * processed could be: email sent, invalid, users do not want to receive emails
	 */
	public function clearProcessedEvent() {
		global $wgUpdateRowsPerQuery;
		$eventMapper = new EventMapper();
		$dbFactory = DbFactory::newFromDefault();
		$dbw = $dbFactory->getEchoDb( DB_PRIMARY );
		$dbr = $dbFactory->getEchoDb( DB_REPLICA );
		$lbFactory = MediaWikiServices::getInstance()->getDBLoadBalancerFactory();
		$ticket = $lbFactory->getEmptyTransactionTicket( __METHOD__ );
		$domainId = $dbw->getDomainID();

		$iterator = new BatchRowIterator( $dbr, 'echo_email_batch', 'eeb_event_id', $wgUpdateRowsPerQuery );
		$iterator->addConditions( [ 'eeb_user_id' => $this->mUser->getId() ] );
		if ( $this->lastEvent ) {
			// There is a processed cutoff point
			$iterator->addConditions( [ 'eeb_event_id <= ' . (int)$this->lastEvent ] );
		}
		$iterator->setCaller( __METHOD__ );

		foreach ( $iterator as $batch ) {
			$eventIds = [];
			foreach ( $batch as $row ) {
				$eventIds[] = $row->eeb_event_id;
			}
			$dbw->delete( 'echo_email_batch', [
				'eeb_user_id' => $this->mUser->getId(),
				'eeb_event_id' => $eventIds
			], __METHOD__ );

			// Find out which events are now orphaned, i.e. no longer referenced in echo_email_batch
			// (besides the rows we just deleted) or in echo_notification, and delete them
			$eventMapper->deleteOrphanedEvents( $eventIds, $this->mUser->getId(), 'echo_email_batch' );

			$lbFactory->commitAndWaitForReplication(
				__METHOD__, $ticket, [ 'domain' => $domainId ] );
		}
	}

	/**
	 * Send the batch email
	 */
	public function sendEmail() {
		global $wgPasswordSender, $wgNoReplyAddress;

		if ( $this->userOptionsManager->getOption( $this->mUser, 'echo-email-frequency' )
			== EmailFrequency::WEEKLY_DIGEST
		) {
			$frequency = 'weekly';
			$emailDeliveryMode = 'weekly_digest';
		} else {
			$frequency = 'daily';
			$emailDeliveryMode = 'daily_digest';
		}

		$textEmailDigestFormatter = new EchoPlainTextDigestEmailFormatter( $this->mUser, $this->language, $frequency );
		$content = $textEmailDigestFormatter->format( $this->events, 'email' );

		if ( !$content ) {
			// no event could be formatted
			return;
		}

		$format = NotifUser::newFromUser( $this->mUser )->getEmailFormat();
		if ( $format == EmailFormat::HTML ) {
			$htmlEmailDigestFormatter = new EchoHtmlDigestEmailFormatter( $this->mUser, $this->language, $frequency );
			$htmlContent = $htmlEmailDigestFormatter->format( $this->events, 'email' );

			$content = [
				'body' => [
					'text' => $content['body'],
					'html' => $htmlContent['body'],
				],
				'subject' => $htmlContent['subject'],
			];
		}

		$toAddress = MailAddress::newFromUser( $this->mUser );
		$fromAddress = new MailAddress( $wgPasswordSender, wfMessage( 'emailsender' )->inContentLanguage()->text() );
		$replyTo = new MailAddress( $wgNoReplyAddress );

		// @Todo Push the email to job queue or just send it out directly?
		UserMailer::send( $toAddress, $fromAddress, $content['subject'], $content['body'], [ 'replyTo' => $replyTo ] );
	}

	/**
	 * Insert notification event into email queue
	 *
	 * @param int $userId
	 * @param int $eventId
	 * @param int $priority
	 * @param string $hash
	 */
	public static function addToQueue( $userId, $eventId, $priority, $hash ) {
		if ( !$userId || !$eventId ) {
			return;
		}

		$dbw = DbFactory::newFromDefault()->getEchoDb( DB_PRIMARY );

		$row = [
			'eeb_user_id' => $userId,
			'eeb_event_id' => $eventId,
			'eeb_event_priority' => $priority,
			'eeb_event_hash' => $hash
		];

		$dbw->insert(
			'echo_email_batch',
			$row,
			__METHOD__,
			[ 'IGNORE' ]
		);
	}

	/**
	 * Get a list of users to be notified for the batch
	 *
	 * @param int $startUserId
	 * @param int $batchSize
	 *
	 * @return IResultWrapper|bool
	 */
	public static function getUsersToNotify( $startUserId, $batchSize ) {
		$dbr = DbFactory::newFromDefault()->getEchoDb( DB_REPLICA );
		$res = $dbr->select(
			[ 'echo_email_batch' ],
			[ 'eeb_user_id' ],
			[ 'eeb_user_id > ' . (int)$startUserId ],
			__METHOD__,
			[ 'ORDER BY' => 'eeb_user_id', 'LIMIT' => $batchSize ]
		);

		return $res;
	}
}

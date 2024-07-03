<?php

namespace MediaWiki\Extension\Notifications;

use MailAddress;
use MediaWiki\Extension\Notifications\Formatters\EchoHtmlEmailFormatter;
use MediaWiki\Extension\Notifications\Formatters\EchoPlainTextEmailFormatter;
use MediaWiki\Extension\Notifications\Hooks\HookRunner;
use MediaWiki\Extension\Notifications\Model\Event;
use MediaWiki\Extension\Notifications\Model\Notification;
use MediaWiki\MediaWikiServices;
use MediaWiki\User\User;
use UserMailer;

class Notifier {
	/**
	 * Record a Notification for an Event
	 * Currently used for web-based notifications.
	 *
	 * @param User $user User to notify.
	 * @param Event $event Event to notify about.
	 */
	public static function notifyWithNotification( $user, $event ) {
		// Only create the notification if the user wants to receive that type
		// of notification, and they are eligible to receive it. See bug 47664.
		$attributeManager = Services::getInstance()->getAttributeManager();
		$userWebNotifications = $attributeManager->getUserEnabledEvents( $user, 'web' );
		if ( !in_array( $event->getType(), $userWebNotifications ) ) {
			return;
		}

		Notification::create( [ 'user' => $user, 'event' => $event ] );
	}

	/**
	 * Send a Notification to a user by email
	 *
	 * @param User $user User to notify.
	 * @param Event $event Event to notify about.
	 * @return bool
	 */
	public static function notifyWithEmail( $user, $event ) {
		global $wgEnableEmail, $wgBlockDisablesLogin, $wgEchoWatchlistEmailOncePerPage, $wgEnotifMinorEdits;
		$services = MediaWikiServices::getInstance();
		$userOptionsLookup = $services->getUserOptionsLookup();

		if (
			// Email is globally disabled
			!$wgEnableEmail ||
			// User does not have a valid and confirmed email address
			!$user->isEmailConfirmed() ||
			// User has disabled Echo emails
			$userOptionsLookup->getOption( $user, 'echo-email-frequency' ) < 0 ||
			// User is blocked and cannot log in (T199993)
			( $wgBlockDisablesLogin && $user->getBlock() )
		) {
			return false;
		}

		$type = $event->getType();
		if ( $type === 'edit-user-talk' ) {
			$extra = $event->getExtra();
			if ( !empty( $extra['minoredit'] ) ) {
				if ( !$wgEnotifMinorEdits || !$userOptionsLookup->getOption( $user, 'enotifminoredits' ) ) {
					// Do not send talk page notification email
					return false;
				}
			}
		// Mimic core code of only sending watchlist notification emails once per page
		} elseif ( $type === "watchlist-change" || $type === "minor-watchlist-change" ) {
			// Don't care about rate limiting
			if ( $wgEchoWatchlistEmailOncePerPage ) {
				$store = $services->getWatchedItemStore();
				$ts = $store->getWatchedItem( $user, $event->getTitle() )->getNotificationTimestamp();
				// if (ts != null) is not sufficient because, if $wgEchoUseJobQueue is set,
				// wl_notificationtimestamp will have already been set for the new edit
				// by the time this code runs.
				if ( $ts !== null && $ts !== $event->getExtraParam( "timestamp" ) ) {
					// User has already seen an email for this page before
					return false;
				}
			}
		}

		$hookRunner = new HookRunner( $services->getHookContainer() );
		// Final check on whether to send email for this user & event
		if ( !$hookRunner->onEchoAbortEmailNotification( $user, $event ) ) {
			return false;
		}

		$attributeManager = Services::getInstance()->getAttributeManager();
		$userEmailNotifications = $attributeManager->getUserEnabledEvents( $user, 'email' );
		// See if the user wants to receive emails for this category or the user is eligible to receive this email
		if ( in_array( $event->getType(), $userEmailNotifications ) ) {
			global $wgEchoEnableEmailBatch, $wgEchoNotifications, $wgPasswordSender, $wgNoReplyAddress;

			$priority = $attributeManager->getNotificationPriority( $event->getType() );

			$bundleString = $bundleHash = '';

			// We should have bundling for email digest as long as either web or email bundling is on,
			// for example, talk page email bundling is off, but if a user decides to receive email
			// digest, we should bundle those messages
			if ( !empty( $wgEchoNotifications[$event->getType()]['bundle']['web'] ) ||
				!empty( $wgEchoNotifications[$event->getType()]['bundle']['email'] )
			) {
				self::getBundleRules( $event, $bundleString );
			}
			if ( $bundleString ) {
				$bundleHash = md5( $bundleString );
			}

			// email digest notification ( weekly or daily )
			if ( $wgEchoEnableEmailBatch && $userOptionsLookup->getOption( $user, 'echo-email-frequency' ) > 0 ) {
				// always create a unique event hash for those events don't support bundling
				// this is mainly for group by
				if ( !$bundleHash ) {
					$bundleHash = md5( $event->getType() . '-' . $event->getId() );
				}
				EmailBatch::addToQueue( $user->getId(), $event->getId(), $priority, $bundleHash );

				return true;
			}

			// instant email notification
			$toAddress = MailAddress::newFromUser( $user );
			$fromAddress = new MailAddress(
				$wgPasswordSender,
				wfMessage( 'emailsender' )->inContentLanguage()->text()
			);
			$replyAddress = new MailAddress( $wgNoReplyAddress );
			// Since we are sending a single email, should set the bundle hash to null
			// if it is set with a value from somewhere else
			$event->setBundleHash( null );
			$email = self::generateEmail( $event, $user );
			if ( !$email ) {
				return false;
			}
			$subject = $email['subject'];
			$body = $email['body'];
			$options = [ 'replyTo' => $replyAddress ];

			UserMailer::send( $toAddress, $fromAddress, $subject, $body, $options );
		}

		return true;
	}

	/**
	 * Handler to get bundle rules, handles echo's own events and calls the EchoGetBundleRule hook,
	 * which defines the bundle rule for the extensions notification.
	 *
	 * @param Event $event
	 * @param string &$bundleString Determines how the notification should be bundled, for example,
	 * talk page notification is bundled based on namespace and title, the bundle string would be
	 * 'edit-user-talk-' + namespace + title, email digest/email bundling would use this hash as
	 * a key to identify bundle-able event.  For web bundling, we bundle further based on user's
	 * visit to the overlay, we would generate a display hash based on the hash of $bundleString
	 */
	public static function getBundleRules( $event, &$bundleString ) {
		switch ( $event->getType() ) {
			case 'edit-user-page':
			case 'edit-user-talk':
			case 'page-linked':
				$bundleString = $event->getType();
				if ( $event->getTitle() ) {
					$bundleString .= '-' . $event->getTitle()->getNamespace()
						. '-' . $event->getTitle()->getDBkey();
				}
				break;
			case 'mention-success':
			case 'mention-failure':
				$bundleString = 'mention-status-' . $event->getExtraParam( 'revid' );
				break;
			case 'watchlist-change':
			case 'minor-watchlist-change':
				$bundleString = 'watchlist-change';
				if ( $event->getTitle() ) {
					$bundleString .= '-' . $event->getTitle()->getNamespace()
						. '-' . $event->getTitle()->getDBkey();
				}
				break;
			default:
				$hookRunner = new HookRunner( MediaWikiServices::getInstance()->getHookContainer() );
				$hookRunner->onEchoGetBundleRules( $event, $bundleString );
		}
	}

	/**
	 * @param Event $event
	 * @param User $user
	 * @return array|false An array of 'subject' and 'body', or false if things went wrong
	 */
	private static function generateEmail( Event $event, User $user ) {
		$emailFormat = NotifUser::newFromUser( $user )->getEmailFormat();
		$services = MediaWikiServices::getInstance();
		$userOptionsLookup = $services->getUserOptionsLookup();
		$lang = $services->getLanguageFactory()
			->getLanguage( $userOptionsLookup->getOption( $user, 'language' ) );
		$formatter = new EchoPlainTextEmailFormatter( $user, $lang );
		$content = $formatter->format( $event, 'email' );
		if ( !$content ) {
			return false;
		}

		if ( $emailFormat === EmailFormat::HTML ) {
			$htmlEmailFormatter = new EchoHtmlEmailFormatter( $user, $lang );
			$htmlContent = $htmlEmailFormatter->format( $event, 'email' );
			$multipartBody = [
				'text' => $content['body'],
				'html' => $htmlContent['body']
			];
			$content['body'] = $multipartBody;
		}

		return $content;
	}
}

class_alias( Notifier::class, 'EchoNotifier' );

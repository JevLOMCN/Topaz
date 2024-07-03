<?php

namespace LoginNotify;

use MediaWiki\Extension\Notifications\Formatters\EchoEventPresentationModel;
use MediaWiki\Extension\Notifications\Model\Event;
use MediaWiki\SpecialPage\SpecialPage;
use Message;

class PresentationModel extends EchoEventPresentationModel {

	/**
	 * Show an user avatar.
	 *
	 * @return string Name of icon
	 */
	public function getIconType() {
		return 'LoginNotify-user-avatar';
	}

	/**
	 * Link to help page on mediawiki
	 *
	 * @return array URL to link to
	 */
	public function getPrimaryLink() {
		return [
			'url' => 'https://mediawiki.org/wiki/Special:MyLanguage/Help:Login_notifications',
			'label' => $this->msg( 'loginnotify-primary-link' )->text()
		];
	}

	/**
	 * Define the email subject string
	 *
	 * @return Message Email subject
	 */
	public function getSubjectMessage() {
		switch ( $this->event->getType() ) {
			case 'login-fail-known':
			case 'login-fail-new':
				$msg = $this->msg( 'notification-loginnotify-login-fail-email-subject' );
				$msg->params( $this->getUser()->getName() );
				$msg->numParams( $this->event->getExtraParam( 'count', 0 ) );
				break;
			default:
				$msg = $this->msg( 'notification-loginnotify-login-success-email-subject' );
				$msg->params( $this->getUser()->getName() );
				break;
		}
		return $msg;
	}

	/**
	 * Include the number of attempts in the message if needed
	 *
	 * @return Message
	 */
	public function getHeaderMessage() {
		switch ( $this->event->getType() ) {
			// Known IP? Don't bundle because we issue notifications after every 5 attempts anyway
			case 'login-fail-known':
				$msg = $this->msg( 'notification-known-header-login-fail' );
				$msg->numParams( $this->event->getExtraParam( 'count', 0 ) );
				break;
			// New IP?
			case 'login-fail-new':
				// If it's a bundle, pass it the bundle count as param
				if ( $this->isBundled() ) {
					$msg = $this->msg( 'notification-new-bundled-header-login-fail' );
					$totalAttempts = array_reduce(
						$this->getBundledEvents(),
						static function ( $sum, Event $event ) {
							return $sum + $event->getExtraParam( 'count', 0 );
						},
						0
					);
					$msg->numParams( $totalAttempts );
				} else {
					// If the bundle is read or user goes to Special:Notifications, show
					// one notification per attempt (aligned with how unbundled bundles work)
					$msg = $this->msg( 'notification-new-unbundled-header-login-fail' );
					$msg->numParams( $this->event->getExtraParam( 'count', 0 ) );
				}
				break;
			default:
				$msg = $this->msg( 'notification-header-login-success', $this->getUser()->getName() );
		}
		return $msg;
	}

	/**
	 * Get links to be used in the notification
	 *
	 * @return array Link to Special:ChangePassword
	 */
	public function getSecondaryLinks() {
		$changePasswordLink = [
			'url' => SpecialPage::getTitleFor( 'ChangePassword' )->getFullURL(),
			'label' => $this->msg( 'changepassword' )->text(),
			'description' => '',
			'icon' => 'lock',
			'prioritized' => true,
		];

		return [ $changePasswordLink ];
	}

}

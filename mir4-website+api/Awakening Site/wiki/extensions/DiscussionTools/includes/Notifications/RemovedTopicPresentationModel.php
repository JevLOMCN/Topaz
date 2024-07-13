<?php
/**
 * EchoEventPresentationModel for removed subscribable section headings
 *
 * @file
 * @ingroup Extensions
 * @license MIT
 */

namespace MediaWiki\Extension\DiscussionTools\Notifications;

use Language;
use MediaWiki\Extension\Notifications\Formatters\EchoEventPresentationModel;
use MediaWiki\Extension\Notifications\Model\Event;
use MediaWiki\MediaWikiServices;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\User\User;

class RemovedTopicPresentationModel extends EchoEventPresentationModel {

	protected PlaintextEchoPresentationModelSection $section;

	/**
	 * @inheritDoc
	 */
	protected function __construct( Event $event, Language $language, User $user, $distributionType ) {
		parent::__construct( $event, $language, $user, $distributionType );
		$this->section = new PlaintextEchoPresentationModelSection( $event, $user, $language );
	}

	/**
	 * @inheritDoc
	 */
	public function getIconType() {
		return 'placeholder';
	}

	/**
	 * @inheritDoc
	 */
	public function canRender() {
		return (bool)$this->event->getTitle();
	}

	/**
	 * @inheritDoc
	 */
	public function getPrimaryLink() {
		$rev = $this->event->getExtraParam( 'revid' );

		if ( $this->isBundled() ) {
			// No link, unless all of these events are from one edit
			foreach ( $this->getBundledEvents() as $event ) {
				if ( $event->getExtraParam( 'revid' ) !== $rev ) {
					return false;
				}
			}
		}

		$title = $this->event->getTitle();
		$url = $title->getLocalURL( [
			'oldid' => 'prev',
			'diff' => $rev
		] );

		return [
			'url' => $url,
			'label' => $this->msg( 'notification-link-text-view-changes', $this->getViewingUserForGender() )->text(),
		];
	}

	/**
	 * @inheritDoc
	 */
	protected function getHeaderMessageKey() {
		if ( $this->isBundled() ) {
			return 'discussiontools-notification-removed-topic-header-bundled';
		} else {
			return 'discussiontools-notification-removed-topic-header';
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getHeaderMessage() {
		if ( $this->isBundled() ) {
			$msg = $this->msg( $this->getHeaderMessageKey() );
			$msg->numParams( $this->getNotificationCountForOutput() );
			$msg->params( $this->getTruncatedTitleText( $this->event->getTitle(), true ) );
			return $msg;
		} else {
			$msg = parent::getHeaderMessage();
			$msg->params( $this->getTruncatedTitleText( $this->event->getTitle(), true ) );
			$msg->plaintextParams( $this->section->getTruncatedSectionTitle() );
			return $msg;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getCompactHeaderMessage() {
		$msg = $this->getMessageWithAgent( 'discussiontools-notification-removed-topic-header-compact' );
		$msg->params( $this->getTruncatedTitleText( $this->event->getTitle(), true ) );
		$msg->plaintextParams( $this->section->getTruncatedSectionTitle() );
		return $msg;
	}

	/**
	 * @inheritDoc
	 */
	public function getBodyMessage() {
		return $this->msg( 'discussiontools-notification-removed-topic-body' )
			->numParams( $this->getNotificationCountForOutput() );
	}

	/**
	 * @inheritDoc
	 */
	public function getSecondaryLinks() {
		global $wgEchoNotifiers;

		$viewPageLink = [
			'url' => $this->event->getTitle()->getFullURL(),
			'label' => $this->msg( 'discussiontools-notification-removed-topic-view' )->text(),
			'description' => '',
			'icon' => 'article',
			'prioritized' => true,
		];

		$links = [
			$viewPageLink,
		];

		// Known preferences used below:
		// * echo-subscriptions-email-dt-subscription-archiving
		// * echo-subscriptions-push-dt-subscription-archiving
		// * echo-subscriptions-web-dt-subscription-archiving

		$category = $this->getCategory();
		$isDisabled = true;
		$optionsLookup = MediaWikiServices::getInstance()->getUserOptionsLookup();
		foreach ( $wgEchoNotifiers as $notifierType => $unused ) {
			if ( $optionsLookup->getOption( $this->getUser(), "echo-subscriptions-$notifierType-$category" ) ) {
				$isDisabled = false;
			}
		}

		if ( !$isDisabled ) {
			$setPrefs = [];
			foreach ( $wgEchoNotifiers as $notifierType => $unused ) {
				$setPrefs[] = "echo-subscriptions-$notifierType-$category=0";
			}

			$links[] = $this->getDynamicActionLink(
				SpecialPage::getTitleFor( 'Preferences', false, 'mw-prefsection-echo-echosubscriptions' ),
				'unbell',
				$this->msg( 'discussiontools-notification-removed-topic-disable' )->text(),
				null,
				[
					'tokenType' => 'csrf',
					'params' => [
						'action' => 'options',
						'change' => implode( '|', $setPrefs ),
					],
					'messages' => [
						'confirmation' => [
							'title' => $this->msg( 'discussiontools-notification-removed-topic-disabled-title' ),
							'description' => $this->msg( 'discussiontools-notification-removed-topic-disabled-body' )
						]
					]
				],
				[]
			);
		}

		return $links;
	}
}

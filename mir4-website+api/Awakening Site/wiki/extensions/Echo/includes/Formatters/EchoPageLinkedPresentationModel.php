<?php

namespace MediaWiki\Extension\Notifications\Formatters;

use MediaWiki\Extension\Notifications\Controller\NotificationController;
use MediaWiki\Extension\Notifications\Model\Event;
use MediaWiki\MediaWikiServices;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Title\Title;

class EchoPageLinkedPresentationModel extends EchoEventPresentationModel {

	/** @var Title|null */
	private $pageFrom;

	public function getIconType() {
		return 'linked';
	}

	/**
	 * The page containing the link may be a new page
	 * that is not yet replicated.
	 * This event won't be rendered unless/until
	 * both pages are available.
	 * @return bool
	 */
	public function canRender() {
		$pageTo = $this->event->getTitle();
		$pageFrom = $this->getPageFrom();
		return (bool)$pageTo && (bool)$pageFrom;
	}

	public function getPrimaryLink() {
		if ( $this->isBundled() ) {
			return false;
		} else {
			$params = [];
			if ( $this->event->getExtraParam( 'revid' ) ) {
				$params = [
					'oldid' => 'prev',
					'diff' => $this->event->getExtraParam( 'revid' )
				];
			}

			return [
				'url' => $this->getPageFrom()->getFullURL( $params ),
				'label' => $this->msg( 'notification-link-text-view-page' )->text(),
			];
		}
	}

	public function getSecondaryLinks() {
		$whatLinksHereLink = [
			'url' => SpecialPage::getTitleFor( 'Whatlinkshere', $this->event->getTitle()->getPrefixedText() )
				->getFullURL(),
			'label' => $this->msg( 'notification-link-text-what-links-here' )->text(),
			'description' => '',
			'icon' => 'linked',
			'prioritized' => true
		];

		return [ $whatLinksHereLink, $this->getMuteLink() ];
	}

	protected function getMuteLink() {
		if ( !MediaWikiServices::getInstance()->getMainConfig()->get( 'EchoPerUserBlacklist' ) ) {
			return null;
		}
		$title = $this->event->getTitle();
		$isPageMuted = NotificationController::isPageLinkedTitleMutedByUser( $title, $this->getUser() );
		$action = $isPageMuted ? 'unmute' : 'mute';
		$prefTitle = SpecialPage::getTitleFor( 'Preferences', false, 'mw-prefsection-echo-mutedpageslist' );
		$data = [
			'tokenType' => 'csrf',
			'params' => [
				'action' => 'echomute',
				'type' => 'page-linked-title',
			],
			'messages' => [
				'confirmation' => [
					// notification-dynamic-actions-mute-page-linked-confirmation
					// notification-dynamic-actions-unmute-page-linked-confirmation
					'title' => $this
						->msg( 'notification-dynamic-actions-' . $action . '-page-linked-confirmation' )
						->params(
							$this->getTruncatedTitleText( $title ),
							$this->getViewingUserForGender()
						),
					// notification-dynamic-actions-mute-page-linked-confirmation-description
					// notification-dynamic-actions-unmute-page-linked-confirmation-description
					'description' => $this
						->msg( 'notification-dynamic-actions-' . $action . '-page-linked-confirmation-description' )
						->params(
							$prefTitle->getFullURL(),
							$this->getViewingUserForGender()
						)
				]
			]
		];
		$data['params'][$isPageMuted ? 'unmute' : 'mute'] = $title->getPrefixedText();

		return $this->getDynamicActionLink(
			$prefTitle,
			$isPageMuted ? 'bell' : 'unbell',
			// notification-dynamic-actions-mute-page-linked
			// notification-dynamic-actions-unmute-page-linked
			$this->msg( 'notification-dynamic-actions-' . $action . '-page-linked' )
				->params(
					$this->getTruncatedTitleText( $title ),
					$this->getViewingUserForGender()
				)->text(),
			null,
			$data,
			[]
		);
	}

	protected function getHeaderMessageKey() {
		if ( $this->getBundleCount( true, [ $this, 'getLinkedPageId' ] ) > 1 ) {
			return 'notification-bundle-header-page-linked';
		}
		return 'notification-header-page-linked';
	}

	public function getHeaderMessage() {
		$msg = parent::getHeaderMessage();
		$msg->params( $this->getTruncatedTitleText( $this->event->getTitle(), true ) );
		$msg->params( $this->getTruncatedTitleText( $this->getPageFrom(), true ) );
		$count =
			$this->getNotificationCountForOutput( true, [ $this, 'getLinkedPageId' ] );

		// Repeat is B/C until unused parameter is removed from translations
		$msg->numParams( $count, $count );
		return $msg;
	}

	public function getCompactHeaderMessage() {
		$msg = $this->msg( parent::getCompactHeaderMessageKey() );
		$msg->params( $this->getTruncatedTitleText( $this->getPageFrom(), true ) );
		return $msg;
	}

	/**
	 * Get the page ID of the linked-from page for a given event.
	 * @param Event $event page-linked event
	 * @return int Page ID, or 0 if the page doesn't exist
	 */
	public function getLinkedPageId( Event $event ) {
		$extra = $event->getExtra();
		if ( isset( $extra['link-from-page-id'] ) ) {
			return $extra['link-from-page-id'];
		}
		// Backwards compatibility for events from before https://gerrit.wikimedia.org/r/#/c/63076
		if ( isset( $extra['link-from-namespace'] ) && isset( $extra['link-from-title'] ) ) {
			$title = Title::makeTitleSafe( $extra['link-from-namespace'], $extra['link-from-title'] );
			if ( $title ) {
				return $title->getArticleID();
			}
		}
		return 0;
	}

	private function getPageFrom() {
		if ( !$this->pageFrom ) {
			$this->pageFrom = Title::newFromID( $this->getLinkedPageId( $this->event ) );
		}
		return $this->pageFrom;
	}

	protected function getSubjectMessageKey() {
		return 'notification-page-linked-email-subject';
	}
}

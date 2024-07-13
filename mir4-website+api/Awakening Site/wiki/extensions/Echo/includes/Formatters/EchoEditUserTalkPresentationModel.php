<?php

namespace MediaWiki\Extension\Notifications\Formatters;

use Language;
use MediaWiki\Extension\Notifications\Model\Event;
use MediaWiki\MediaWikiServices;
use MediaWiki\User\User;

class EchoEditUserTalkPresentationModel extends EchoEventPresentationModel {

	/**
	 * @var EchoPresentationModelSection
	 */
	protected $section;

	/**
	 * @inheritDoc
	 */
	protected function __construct( Event $event, Language $language, User $user, $distributionType ) {
		parent::__construct( $event, $language, $user, $distributionType );
		$this->section = new EchoPresentationModelSection( $event, $user, $language );
	}

	public function canRender() {
		return (bool)$this->event->getTitle();
	}

	public function getIconType() {
		return 'edit-user-talk';
	}

	public function getPrimaryLink() {
		return [
			// Need FullURL so the section is included
			'url' => $this->section->getTitleWithSection()->getFullURL(),
			'label' => $this->msg( 'notification-link-text-view-message' )->text()
		];
	}

	public function getSecondaryLinks() {
		$diffLink = [
			'url' => $this->getDiffLinkUrl(),
			'label' => $this->msg( 'notification-link-text-view-changes', $this->getViewingUserForGender() )->text(),
			'description' => '',
			'icon' => 'changes',
			'prioritized' => true
		];

		if ( $this->isBundled() ) {
			return [ $diffLink ];
		} else {
			return [ $this->getAgentLink(), $diffLink ];
		}
	}

	public function getHeaderMessage() {
		if ( $this->isBundled() ) {
			$msg = $this->msg( 'notification-bundle-header-edit-user-talk-v2' );
			$count = $this->getNotificationCountForOutput();

			// Repeat is B/C until unused parameter is removed from translations
			$msg->numParams( $count, $count );
			$msg->params( $this->getViewingUserForGender() );
			return $msg;
		} elseif ( $this->section->exists() ) {
			$msg = $this->getMessageWithAgent( 'notification-header-edit-user-talk-with-section' );
			$msg->params( $this->getViewingUserForGender() );
			$msg->plaintextParams( $this->section->getTruncatedSectionTitle() );
			return $msg;
		} else {
			$msg = parent::getHeaderMessage();
			$msg->params( $this->getViewingUserForGender() );
			return $msg;
		}
	}

	public function getCompactHeaderMessage() {
		$hasSection = $this->section->exists();
		$key = $hasSection
			? 'notification-compact-header-edit-user-talk-with-section'
			: 'notification-compact-header-edit-user-talk';
		$msg = $this->getMessageWithAgent( $key );
		$msg->params( $this->getViewingUserForGender() );
		if ( $hasSection ) {
			$msg->params( $this->section->getTruncatedSectionTitle() );
		}
		return $msg;
	}

	public function getBodyMessage() {
		$sectionText = $this->event->getExtraParam( 'section-text' );
		if ( !$this->isBundled() && $this->section->exists() && is_string( $sectionText ) ) {
			$msg = $this->msg( 'notification-body-edit-user-talk-with-section' );
			// section-text is safe to use here, because section->exists() returns false if the revision is deleted
			$msg->plaintextParams( $sectionText );
			return $msg;
		} else {
			return false;
		}
	}

	private function getDiffLinkUrl() {
		$revId = $this->event->getExtraParam( 'revid' );
		$oldId = $this->isBundled() ? $this->getRevBeforeFirstNotification() : 'prev';
		$query = [
			'oldid' => $oldId,
			'diff' => $revId,
		];
		return $this->event->getTitle()->getFullURL( $query );
	}

	private function getRevBeforeFirstNotification() {
		$events = $this->getBundledEvents();
		$firstNotificationRevId = end( $events )->getExtraParam( 'revid' );
		$revisionLookup = MediaWikiServices::getInstance()->getRevisionLookup();
		$revisionRecord = $revisionLookup->getRevisionById( $firstNotificationRevId );
		$previousRevision = $revisionRecord ? $revisionLookup->getPreviousRevision( $revisionRecord ) : null;
		return $previousRevision ? $previousRevision->getId() : 0;
	}

	protected function getSubjectMessageKey() {
		return 'notification-edit-talk-page-email-subject2';
	}
}

class_alias( EchoEditUserTalkPresentationModel::class, 'EchoEditUserTalkPresentationModel' );

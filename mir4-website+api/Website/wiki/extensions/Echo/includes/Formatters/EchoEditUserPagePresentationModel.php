<?php

namespace MediaWiki\Extension\Notifications\Formatters;

use MediaWiki\Language\RawMessage;
use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\Sanitizer;
use MediaWiki\Revision\RevisionRecord;
use Message;

class EchoEditUserPagePresentationModel extends EchoEventPresentationModel {

	public function canRender() {
		return (bool)$this->event->getTitle();
	}

	public function getIconType() {
		return 'edit-user-page';
	}

	public function getPrimaryLink() {
		return [
			'url' => $this->getDiffLinkUrl(),
			'label' => $this->msg( 'notification-link-text-view-edit' )->text()
		];
	}

	public function getSecondaryLinks() {
		if ( $this->isBundled() ) {
			return [];
		} else {
			return [ $this->getAgentLink() ];
		}
	}

	public function getHeaderMessage() {
		if ( $this->isBundled() ) {
			$msg = $this->msg( 'notification-bundle-header-edit-user-page' );
			$count = $this->getNotificationCountForOutput();

			$msg->numParams( $count );
			$msg->params( $this->getViewingUserForGender() );
			return $msg;
		} else {
			$msg = parent::getHeaderMessage();
			$msg->params( $this->getViewingUserForGender() );
			return $msg;
		}
	}

	public function getCompactHeaderMessage() {
		$msg = $this->getMessageWithAgent( 'notification-compact-header-edit-user-page' );
		$msg->params( $this->getViewingUserForGender() );
		return $msg;
	}

	public function getBodyMessage() {
		$revision = $this->event->getRevision();
		if ( $revision && $revision->getComment() && $this->userCan( RevisionRecord::DELETED_COMMENT ) ) {
			$summary = $revision->getComment()->text;
			$summary = MediaWikiServices::getInstance()->getCommentFormatter()->format( $summary );
			$summary = Sanitizer::stripAllTags( $summary );
		} else {
			$summary = false;
		}
		if ( !$this->isBundled() ) {
			return new RawMessage( '$1', [ Message::plaintextParam( $summary ) ] );
		}
		return false;
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
		$oldRevisionID = $previousRevision ? $previousRevision->getId() : 0;

		return $oldRevisionID;
	}

	protected function getSubjectMessageKey() {
		return 'notification-edit-user-page-email-subject';
	}
}

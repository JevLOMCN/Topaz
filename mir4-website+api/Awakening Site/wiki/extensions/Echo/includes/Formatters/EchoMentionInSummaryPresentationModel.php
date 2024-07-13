<?php

namespace MediaWiki\Extension\Notifications\Formatters;

use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\Sanitizer;
use MediaWiki\Revision\RevisionRecord;

class EchoMentionInSummaryPresentationModel extends EchoEventPresentationModel {

	public function getIconType() {
		return 'mention';
	}

	public function canRender() {
		return (bool)$this->event->getTitle();
	}

	public function getHeaderMessage() {
		$msg = $this->getMessageWithAgent( 'notification-header-mention-summary' );
		$msg->params( $this->getViewingUserForGender() );
		$msg->params( $this->getTruncatedTitleText( $this->event->getTitle(), true ) );

		return $msg;
	}

	public function getBodyMessage() {
		$revision = $this->event->getRevision();
		if ( $revision && $revision->getComment() && $this->userCan( RevisionRecord::DELETED_COMMENT ) ) {
			$summary = $revision->getComment()->text;
			$summary = MediaWikiServices::getInstance()->getCommentFormatter()
				->format( $summary );
			$summary = Sanitizer::stripAllTags( $summary );

			return $this->msg( 'notification-body-mention' )
				->plaintextParams( $summary );
		} else {
			return false;
		}
	}

	public function getPrimaryLink() {
		return [
			'url' => $this->getDiffURL(),
			'label' => $this->msg( 'notification-link-text-view-changes', $this->getViewingUserForGender() )->text(),
		];
	}

	public function getSecondaryLinks() {
		return [ $this->getAgentLink() ];
	}

	protected function getSubjectMessageKey() {
		return 'notification-mention-email-subject';
	}

	private function getDiffURL() {
		return $this->event->getTitle()->getLocalURL( [
			'oldid' => 'prev',
			'diff' => $this->event->getExtraParam( 'revid' )
		] );
	}
}

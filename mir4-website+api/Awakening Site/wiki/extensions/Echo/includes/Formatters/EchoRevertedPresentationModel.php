<?php

namespace MediaWiki\Extension\Notifications\Formatters;

use MediaWiki\Extension\Notifications\DiscussionParser;
use MediaWiki\Revision\RevisionRecord;

class EchoRevertedPresentationModel extends EchoEventPresentationModel {

	public function getIconType() {
		return 'revert';
	}

	public function canRender() {
		return (bool)$this->event->getTitle();
	}

	public function getHeaderMessage() {
		$msg = parent::getHeaderMessage();
		$msg->params( $this->getTruncatedTitleText( $this->event->getTitle(), true ) );
		$msg->params( $this->getNumberOfEdits() );
		return $msg;
	}

	public function getBodyMessage() {
		$summary = $this->event->getExtraParam( 'summary' );
		if (
			!$this->isAutomaticSummary( $summary ) &&
			$this->userCan( RevisionRecord::DELETED_COMMENT )
		) {
			$msg = $this->msg( 'notification-body-reverted' );
			$msg->plaintextParams( $this->formatSummary( $summary ) );
			return $msg;
		} else {
			return false;
		}
	}

	/**
	 * @param string|null $wikitext
	 * @return string
	 */
	private function formatSummary( $wikitext ) {
		if ( $wikitext === null || $wikitext === '' ) {
			return '';
		}
		return DiscussionParser::getTextSnippetFromSummary( $wikitext, $this->language );
	}

	public function getPrimaryLink() {
		$url = $this->event->getTitle()->getLocalURL( [
			'oldid' => 'prev',
			'diff' => $this->event->getExtraParam( 'revid' )
		] );
		return [
			'url' => $url,
			'label' => $this->msg( 'notification-link-text-view-changes', $this->getViewingUserForGender() )->text()
		];
	}

	public function getSecondaryLinks() {
		$links = [ $this->getAgentLink() ];

		$title = $this->event->getTitle();
		if ( $title->canHaveTalkPage() ) {
			$links[] = $this->getPageLink(
				$title->getTalkPage(), '', true
			);
		}

		return $links;
	}

	/**
	 * Return a number that represents if one or multiple edits
	 * have been reverted for formatting purposes.
	 * @return int
	 */
	private function getNumberOfEdits() {
		$method = $this->event->getExtraParam( 'method' );
		if ( $method && $method === 'rollback' ) {
			return 2;
		} else {
			return 1;
		}
	}

	/**
	 * @param string|null $summary
	 * @return bool
	 */
	private function isAutomaticSummary( $summary ) {
		if ( $summary === null || $summary === '' ) {
			return false;
		}
		$autoSummaryMsg = $this->msg( 'undo-summary' )->inContentLanguage();
		$autoSummaryMsg->params( $this->event->getExtraParam( 'reverted-revision-id' ) );
		$autoSummaryMsg->params( $this->getViewingUserForGender() );
		$autoSummary = $autoSummaryMsg->text();

		return $summary === $autoSummary;
	}

	protected function getSubjectMessageKey() {
		return 'notification-reverted-email-subject2';
	}

	public function getSubjectMessage() {
		return parent::getSubjectMessage()->params( $this->getNumberOfEdits() );
	}
}

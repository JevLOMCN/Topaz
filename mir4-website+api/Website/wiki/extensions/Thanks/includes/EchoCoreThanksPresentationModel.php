<?php

namespace MediaWiki\Extension\Thanks;

use DatabaseLogEntry;
use LogEntry;
use LogFormatter;
use LogPage;
use MediaWiki\Extension\Notifications\Formatters\EchoEventPresentationModel;
use MediaWiki\Language\RawMessage;
use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\Sanitizer;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\SpecialPage\SpecialPage;

class EchoCoreThanksPresentationModel extends EchoEventPresentationModel {
	/** @var LogEntry|bool|null */
	private $logEntry;

	public function canRender() {
		$hasTitle = (bool)$this->event->getTitle();
		if ( $hasTitle && $this->getThankType() === 'log' ) {
			$logEntry = $this->getLogEntry();
			return $logEntry && !(
				// the notification renders the message on Special:Log without the comment,
				// so check $logEntry is not deleted, or only its comment is deleted
				$logEntry->getDeleted() & ~LogPage::DELETED_COMMENT
			);
		}
		return $hasTitle;
	}

	public function getIconType() {
		return 'thanks';
	}

	public function getHeaderMessage() {
		$type = $this->getThankType();
		if ( $this->isBundled() ) {
			// Message is either notification-bundle-header-rev-thank
			// or notification-bundle-header-log-thank.
			$msg = $this->msg( "notification-bundle-header-$type-thank" );
			$msg->params( $this->getBundleCount() );
			$msg->params( $this->getTruncatedTitleText( $this->event->getTitle(), true ) );
			$msg->params( $this->getViewingUserForGender() );
			return $msg;
		} else {
			if ( $this->event->getExtraParam( 'revcreation', null ) ) {
				// This is a thank on a page creation revision.
				$msg = $this->getMessageWithAgent( "notification-header-creation-thank" );
			} else {
				// Message is either notification-header-rev-thank or notification-header-log-thank.
				$msg = $this->getMessageWithAgent( "notification-header-$type-thank" );
			}
			$msg->params( $this->getTruncatedTitleText( $this->event->getTitle(), true ) );
			$msg->params( $this->getViewingUserForGender() );
			return $msg;
		}
	}

	public function getCompactHeaderMessage() {
		// The following message is used here:
		// * notification-compact-header-edit-thank
		$msg = parent::getCompactHeaderMessage();
		$msg->params( $this->getViewingUserForGender() );
		return $msg;
	}

	public function getBodyMessage() {
		$comment = $this->getRevOrLogComment();
		if ( $comment !== '' ) {
			$msg = new RawMessage( '$1' );
			$msg->plaintextParams( $comment );
			return $msg;
		}
		return false;
	}

	/**
	 * @return string|bool The comment or false if it could not be retrieved.
	 */
	private function getRevisionEditSummary() {
		if ( !$this->userCan( RevisionRecord::DELETED_COMMENT ) ) {
			return false;
		}

		$revision = $this->event->getRevision();
		if ( !$revision ) {
			return false;
		}

		$summary = $revision->getComment( RevisionRecord::RAW );
		return $summary ? $summary->text : false;
	}

	/**
	 * Get the comment/summary/excerpt of the log entry or revision,
	 * for use in the notification body.
	 * @return string
	 */
	protected function getRevOrLogComment(): string {
		if ( $this->event->getExtraParam( 'logid' ) ) {
			$logEntry = $this->getLogEntry();
			if ( !$logEntry ) {
				return '';
			}
			$formatter = LogFormatter::newFromEntry( $logEntry );
			$excerpt = $formatter->getPlainActionText();
			// Turn wikitext into plaintext
			$excerpt = MediaWikiServices::getInstance()->getCommentFormatter()->format( $excerpt );
			return Sanitizer::stripAllTags( $excerpt );
		} else {
			// Try to get edit summary.
			$summary = $this->getRevisionEditSummary();
			if ( $summary !== false && $summary !== '' ) {
				return $summary;
			}
			// Fallback on edit excerpt.
			if ( $this->userCan( RevisionRecord::DELETED_TEXT ) ) {
				return $this->event->getExtraParam( 'excerpt', '' );
			}
			return '';
		}
	}

	public function getPrimaryLink() {
		$logId = $this->event->getExtraParam( 'logid' );
		if ( $logId ) {
			$url = SpecialPage::getTitleFor( 'Log' )->getLocalURL( [ 'logid' => $logId ] );
			$label = 'notification-link-text-view-logentry';
		} else {
			$url = $this->event->getTitle()->getLocalURL( [
				'oldid' => 'prev',
				'diff' => $this->event->getExtraParam( 'revid' )
			] );
			$label = 'notification-link-text-view-edit';
		}
		return [
			'url' => $url,
			// Label is only used for non-JS clients.
			'label' => $this->msg( $label )->text(),
		];
	}

	public function getSecondaryLinks() {
		$pageLink = $this->getPageLink( $this->event->getTitle(), '', true );
		if ( $this->isBundled() ) {
			return [ $pageLink ];
		} else {
			return [ $this->getAgentLink(), $pageLink ];
		}
	}

	/**
	 * @return LogEntry|false
	 */
	private function getLogEntry() {
		if ( $this->logEntry !== null ) {
			return $this->logEntry;
		}
		$logId = $this->event->getExtraParam( 'logid' );
		if ( !$logId ) {
			$this->logEntry = false;
		} else {
			$dbr = MediaWikiServices::getInstance()->getConnectionProvider()->getReplicaDatabase();
			$this->logEntry = DatabaseLogEntry::newFromId( $logId, $dbr ) ?: false;
		}
		return $this->logEntry;
	}

	/**
	 * Returns thank type
	 *
	 * @return string 'log' or 'rev'
	 */
	private function getThankType() {
		return $this->event->getExtraParam( 'logid' ) ? 'log' : 'rev';
	}
}

<?php

namespace MediaWiki\Extension\Notifications\Formatters;

use Language;
use MediaWiki\Extension\Notifications\Model\Event;
use MediaWiki\User\User;

/**
 * Presenter for 'mention-failure' and 'mention-success' notifications
 *
 * @author Christoph Fischer <christoph.fischer@wikimedia.de>
 *
 * @license MIT
 */
class EchoMentionStatusPresentationModel extends EchoEventPresentationModel {

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

	public function getIconType() {
		if ( $this->isMixedBundle() ) {
			return 'mention-status-bundle';
		}
		if ( $this->isMentionSuccess() ) {
			return 'mention-success';
		}
		return 'mention-failure';
	}

	public function canRender() {
		return (bool)$this->event->getTitle();
	}

	public function getHeaderMessage() {
		if ( $this->isTooManyMentionsFailure() ) {
			$msg = $this->getMessageWithAgent( 'notification-header-mention-failure-too-many' );
			$msg->numParams( $this->getMaxMentions() );
			return $msg;
		}

		if ( $this->isBundled() ) {
			if ( $this->isMixedBundle() ) {
				$successCount = $this->getBundleSuccessCount();

				$msg = $this->getMessageWithAgent( 'notification-header-mention-status-bundle' );
				$msg->numParams( $this->getBundleCount() );
				$msg->params( $this->getTruncatedTitleText( $this->event->getTitle() ) );
				$msg->numParams( $this->getBundleCount() - $successCount );
				$msg->numParams( $successCount );
				return $msg;
			}
			if ( $this->isMentionSuccess() ) {
				$msgKey = 'notification-header-mention-success-bundle';
			} else {
				$msgKey = 'notification-header-mention-failure-bundle';
			}
			$msg = $this->getMessageWithAgent( $msgKey );
			$msg->numParams( $this->getBundleCount() );
			$msg->params( $this->getTruncatedTitleText( $this->event->getTitle() ) );
			return $msg;
		}

		if ( $this->isMentionSuccess() ) {
			$msgKey = 'notification-header-mention-success';
		} else {
			// Messages that can be used here:
			// * notification-header-mention-failure-user-unknown
			// * notification-header-mention-failure-user-anonymous
			$msgKey = 'notification-header-mention-failure-' . $this->getFailureType();
		}
		$msg = $this->getMessageWithAgent( $msgKey );
		$msg->params( $this->getSubjectName() );
		return $msg;
	}

	public function getCompactHeaderMessage() {
		if ( $this->isMentionSuccess() ) {
			$msg = $this->getMessageWithAgent( 'notification-compact-header-mention-success' );
		} else {
			// Messages that can be used here:
			// * notification-compact-header-mention-failure-user-unknown
			// * notification-compact-header-mention-failure-user-anonymous
			$msg = $this->msg( 'notification-compact-header-mention-failure-' . $this->getFailureType() );
		}
		$msg->params( $this->getSubjectName() );
		return $msg;
	}

	public function getPrimaryLink() {
		return [
			// Need FullURL so the section is included
			'url' => $this->section->getTitleWithSection()->getFullURL(),
			'label' => $this->msg( 'notification-link-text-view-mention-failure' )
				->numParams( $this->getBundleCount() )
				->text()
		];
	}

	public function getSecondaryLinks() {
		if ( $this->isBundled() ) {
			return [];
		}

		$talkPageLink = $this->getPageLink(
			$this->section->getTitleWithSection(),
			'',
			true
		);

		return [ $talkPageLink ];
	}

	public function isMentionSuccessEvent( Event $event ) {
		return $event->getType() === 'mention-success';
	}

	private function isMentionSuccess() {
		return $this->isMentionSuccessEvent( $this->event );
	}

	private function getSubjectName() {
		return $this->event->getExtraParam( 'subject-name', '' );
	}

	private function getFailureType() {
		return $this->event->getExtraParam( 'failure-type', 'user-unknown' );
	}

	private function isTooManyMentionsFailure() {
		return $this->getFailureType() === 'too-many' ||
			$this->getType() === 'mention-failure-too-many';
	}

	private function getMaxMentions() {
		global $wgEchoMaxMentionsCount;
		return $this->event->getExtraParam( 'max-mentions', $wgEchoMaxMentionsCount );
	}

	private function getBundleSuccessCount() {
		$events = array_merge( $this->getBundledEvents(), [ $this->event ] );
		return count( array_filter( $events, [ $this, 'isMentionSuccessEvent' ] ) );
	}

	private function isMixedBundle() {
		$successCount = $this->getBundleSuccessCount();
		$failCount = $this->getBundleCount() - $successCount;
		return $successCount > 0 && $failCount > 0;
	}
}

class_alias( EchoMentionStatusPresentationModel::class, 'EchoMentionStatusPresentationModel' );

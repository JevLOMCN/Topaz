<?php
/**
 * EchoEventPresentationModel for added subscribable section headings
 *
 * @file
 * @ingroup Extensions
 * @license MIT
 */

namespace MediaWiki\Extension\DiscussionTools\Notifications;

class AddedTopicPresentationModel extends SubscribedNewCommentPresentationModel {

	/**
	 * @inheritDoc
	 */
	public function getPrimaryLink() {
		return [
			'url' => $this->getCommentLink() ?: $this->section->getTitleWithSection()->getFullURL(),
			'label' => $this->msg( 'discussiontools-notification-added-topic-view' )->text()
		];
	}

	/**
	 * @inheritDoc
	 */
	protected function getHeaderMessageKey() {
		if ( $this->isBundled() ) {
			return 'discussiontools-notification-added-topic-header-bundled';
		} else {
			return 'discussiontools-notification-added-topic-header';
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getHeaderMessage() {
		if ( $this->isBundled() ) {
			$count = $this->getNotificationCountForOutput();
			$msg = $this->msg( $this->getHeaderMessageKey() );

			$msg->numParams( $count );
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
		$msg = $this->getMessageWithAgent( 'discussiontools-notification-added-topic-header-compact' );
		$msg->plaintextParams( $this->section->getTruncatedSectionTitle() );
		$msg->plaintextParams( $this->getContentSnippet() );
		return $msg;
	}

	/**
	 * @inheritDoc
	 */
	protected function getUnsubscribeConfirmationMessageKeys() {
		return [
			'title' => 'discussiontools-newtopicssubscription-notify-unsubscribed-title',
			'description' => 'discussiontools-newtopicssubscription-notify-unsubscribed-body',
		];
	}
}

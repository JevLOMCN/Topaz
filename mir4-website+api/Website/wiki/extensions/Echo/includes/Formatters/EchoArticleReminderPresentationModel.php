<?php

namespace MediaWiki\Extension\Notifications\Formatters;

/**
 * Presenter for 'article-reminder' notification
 *
 * @author Ela Opper
 *
 * @license MIT
 */
class EchoArticleReminderPresentationModel extends EchoEventPresentationModel {
	public function canRender() {
		return (bool)$this->event->getTitle();
	}

	public function getIconType() {
		return 'article-reminder';
	}

	public function getHeaderMessage() {
		$msg = $this->getMessageWithAgent( 'notification-header-article-reminder' );
		$msg->params( $this->getTruncatedTitleText( $this->event->getTitle(), true ) );
		return $msg;
	}

	public function getPrimaryLink() {
		return [
			'url' => $this->event->getTitle()->getLocalURL(),
			'label' => $this->msg( 'notification-link-article-reminder' )->text(),
		];
	}
}

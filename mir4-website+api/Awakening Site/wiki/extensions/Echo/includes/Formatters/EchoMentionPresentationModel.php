<?php

namespace MediaWiki\Extension\Notifications\Formatters;

use Language;
use MediaWiki\Extension\Notifications\DiscussionParser;
use MediaWiki\Extension\Notifications\Model\Event;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\User\User;

class EchoMentionPresentationModel extends EchoEventPresentationModel {

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
		return 'mention';
	}

	public function canRender() {
		return (bool)$this->event->getTitle();
	}

	protected function getHeaderMessageKey() {
		$hasSection = $this->section->exists();
		if ( $this->onArticleTalkpage() ) {
			return $hasSection ?
				'notification-header-mention-article-talkpage' :
				'notification-header-mention-article-talkpage-nosection';
		} elseif ( $this->onAgentTalkpage() ) {
			return $hasSection ?
				'notification-header-mention-agent-talkpage' :
				'notification-header-mention-agent-talkpage-nosection';
		} elseif ( $this->onUserTalkpage() ) {
			return $hasSection ?
				'notification-header-mention-user-talkpage-v2' :
				'notification-header-mention-user-talkpage-nosection';
		} else {
			return $hasSection ?
				'notification-header-mention-other' :
				'notification-header-mention-other-nosection';
		}
	}

	public function getHeaderMessage() {
		$msg = $this->getMessageWithAgent( $this->getHeaderMessageKey() );
		$msg->params( $this->getViewingUserForGender() );

		if ( $this->onArticleTalkpage() ) {
			$msg->params( $this->getTruncatedTitleText( $this->event->getTitle() ) );
		} elseif ( $this->onAgentTalkpage() ) {
			// No params to add here.
			// If we remove this check, onUserTalkpage() has to
			// make sure it is a user talk page but NOT the agent's talk page.
		} elseif ( $this->onUserTalkpage() ) {
			$username = $this->event->getTitle()->getText();
			$msg->params( $this->getTruncatedUsername( User::newFromName( $username, false ) ) );
			$msg->params( $username );
		} else {
			$msg->params( $this->getTruncatedTitleText( $this->event->getTitle(), true ) );
		}

		if ( $this->section->exists() ) {
			$msg->plaintextParams( $this->section->getTruncatedSectionTitle() );
		}

		return $msg;
	}

	public function getBodyMessage() {
		$content = $this->event->getExtraParam( 'content' );
		if ( $content && $this->userCan( RevisionRecord::DELETED_TEXT ) ) {
			$msg = $this->msg( 'notification-body-mention' );
			$msg->plaintextParams(
				DiscussionParser::getTextSnippet(
					$content,
					$this->language,
					DiscussionParser::DEFAULT_SNIPPET_LENGTH,
					$this->event->getTitle()
				)
			);
			return $msg;
		} else {
			return false;
		}
	}

	public function getPrimaryLink() {
		return [
			// Need FullURL so the section is included
			'url' => $this->section->getTitleWithSection()->getFullURL(),
			'label' => $this->msg( 'notification-link-text-view-mention' )->text()
		];
	}

	public function getSecondaryLinks() {
		$url = $this->event->getTitle()->getLocalURL( [
			'oldid' => 'prev',
			'diff' => $this->event->getExtraParam( 'revid' )
		] );
		$viewChangesLink = [
			'url' => $url,
			'label' => $this->msg( 'notification-link-text-view-changes', $this->getViewingUserForGender() )->text(),
			'description' => '',
			'icon' => 'changes',
			'prioritized' => true,
		];

		return [ $this->getAgentLink(), $viewChangesLink ];
	}

	private function onArticleTalkpage() {
		return $this->event->getTitle()->getNamespace() === NS_TALK;
	}

	private function onAgentTalkpage() {
		return $this->event->getTitle()->equals( $this->event->getAgent()->getTalkPage() );
	}

	private function onUserTalkpage() {
		$title = $this->event->getTitle();
		return $title->getNamespace() === NS_USER_TALK && !$title->isSubpage();
	}

	protected function getSubjectMessageKey() {
		return 'notification-mention-email-subject';
	}
}

class_alias( EchoMentionPresentationModel::class, 'EchoMentionPresentationModel' );

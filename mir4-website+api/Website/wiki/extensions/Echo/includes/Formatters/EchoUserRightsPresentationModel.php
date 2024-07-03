<?php

namespace MediaWiki\Extension\Notifications\Formatters;

use MediaWiki\Extension\Notifications\DiscussionParser;
use MediaWiki\Language\RawMessage;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\User\User;

/**
 * Formatter for 'user-rights' notifications
 */
class EchoUserRightsPresentationModel extends EchoEventPresentationModel {

	public function getIconType() {
		return 'user-rights';
	}

	public function getHeaderMessage() {
		[ $formattedName, $genderName ] = $this->getAgentForOutput();
		$add = array_map(
			[ $this->language, 'embedBidi' ],
			$this->getLocalizedGroupNames( array_values( $this->event->getExtraParam( 'add', [] ) ) )
		);
		$remove = array_map(
			[ $this->language, 'embedBidi' ],
			$this->getLocalizedGroupNames( array_values( $this->event->getExtraParam( 'remove', [] ) ) )
		);
		$expiryChanged = array_map(
			[ $this->language, 'embedBidi' ],
			$this->getLocalizedGroupNames( array_values( $this->event->getExtraParam( 'expiry-changed', [] ) ) )
		);
		if ( $expiryChanged ) {
			$msg = $this->msg( 'notification-header-user-rights-expiry-change' );
			$msg->params( $genderName );
			$msg->params( $this->language->commaList( $expiryChanged ) );
			$msg->params( count( $expiryChanged ) );
			$msg->params( $this->getViewingUserForGender() );
			return $msg;
		} elseif ( $add && !$remove ) {
			$msg = $this->msg( 'notification-header-user-rights-add-only' );
			$msg->params( $genderName );
			$msg->params( $this->language->commaList( $add ) );
			$msg->params( count( $add ) );
			$msg->params( $this->getViewingUserForGender() );
			return $msg;
		} elseif ( !$add && $remove ) {
			$msg = $this->msg( 'notification-header-user-rights-remove-only' );
			$msg->params( $genderName );
			$msg->params( $this->language->commaList( $remove ) );
			$msg->params( count( $remove ) );
			$msg->params( $this->getViewingUserForGender() );
			return $msg;
		} else {
			$msg = $this->msg( 'notification-header-user-rights-add-and-remove' );
			$msg->params( $genderName );
			$msg->params( $this->language->commaList( $add ) );
			$msg->params( count( $add ) );
			$msg->params( $this->language->commaList( $remove ) );
			$msg->params( count( $remove ) );
			$msg->params( $this->getViewingUserForGender() );
			return $msg;
		}
	}

	public function getBodyMessage() {
		$reason = $this->event->getExtraParam( 'reason' );
		if ( $reason ) {
			$text = DiscussionParser::getTextSnippet( $reason, $this->language );
			return new RawMessage( "$1", [ $text ] );
		}
		return false;
	}

	private function getLocalizedGroupNames( array $names ) {
		return array_map( function ( $name ) {
			$msg = $this->msg( 'group-' . $name );
			return $msg->isBlank() ? $name : $msg->text();
		}, $names );
	}

	public function getPrimaryLink() {
		$addedGroups = array_values( $this->event->getExtraParam( 'add', [] ) );
		$removedGroups = array_values( $this->event->getExtraParam( 'remove', [] ) );
		if ( $addedGroups !== [] && $removedGroups === [] ) {
			$fragment = $addedGroups[0];
		} elseif ( $addedGroups === [] && $removedGroups !== [] ) {
			$fragment = $removedGroups[0];
		} else {
			$fragment = '';
		}
		return [
			'url' => SpecialPage::getTitleFor( 'Listgrouprights', false, $fragment )->getFullURL(),
			'label' => $this->msg( 'echo-learn-more' )->text()
		];
	}

	public function getSecondaryLinks() {
		return [ $this->getAgentLink(), $this->getLogLink() ];
	}

	private function getLogLink() {
		$affectedUserPage = User::newFromId( $this->event->getExtraParam( 'user' ) )->getUserPage();
		$query = [
			'type' => 'rights',
			'page' => $affectedUserPage->getPrefixedText(),
			'user' => $this->event->getAgent()->getName(),
		];
		return [
			'label' => $this->msg( 'echo-log' )->text(),
			'url' => SpecialPage::getTitleFor( 'Log' )->getFullURL( $query ),
			'description' => '',
			'icon' => false,
			'prioritized' => true,
		];
	}

	protected function getSubjectMessageKey() {
		return 'notification-user-rights-email-subject';
	}
}

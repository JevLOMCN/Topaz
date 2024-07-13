<?php

namespace MediaWiki\Extension\Notifications\Formatters;

use MediaWiki\SpecialPage\SpecialPage;

class EchoWatchlistChangePresentationModel extends EchoEventPresentationModel {

	public function getIconType() {
		return 'watchlist-progressive';
	}

	public function getHeaderMessage() {
		if ( $this->isMultiTypeBundle() ) {
			$status = "changed";
		} else {
			$status = $this->event->getExtraParam( 'status' );
		}
		if ( $this->isMultiUserBundle() ) {
			// Messages: notification-header-watchlist-multiuser-changed,
			// notification-header-watchlist-multiuser-created
			// notification-header-watchlist-multiuser-deleted
			// notification-header-watchlist-multiuser-moved
			// notification-header-watchlist-multiuser-restored
			$msg = $this->msg( "notification-header-watchlist-multiuser-" . $status );
		} else {
			// Messages: notification-header-watchlist-changed,
			// notification-header-watchlist-created
			// notification-header-watchlist-deleted
			// notification-header-watchlist-moved
			// notification-header-watchlist-restored
			$msg = $this->getMessageWithAgent( "notification-header-watchlist-" . $status );
		}
		$msg->params( $this->getTruncatedTitleText( $this->event->getTitle(), true ) );
		$msg->params( $this->getViewingUserForGender() );
		$msg->numParams( $this->getBundleCount() );
		return $msg;
	}

	public function getPrimaryLink() {
		if ( $this->isBundled() ) {
			return [
				'url' => $this->event->getTitle()->getLocalUrl(),
				'label' => $this->msg( 'notification-link-text-view-page' )->text()
			];
		}
		return [
			'url' => $this->getViewChangesUrl(),
			'label' => $this->msg( 'notification-link-text-view-changes', $this->getViewingUserForGender() )
				->text(),
		];
	}

	public function getSecondaryLinks() {
		if ( $this->isBundled() ) {
			if ( $this->isMultiUserBundle() ) {
				return [];
			} else {
				return [ $this->getAgentLink() ];
			}
		} else {
			$viewChangesLink = [
				'url' => $this->getViewChangesUrl(),
				'label' => $this->msg( 'notification-link-text-view-changes', $this->getViewingUserForGender() )
					->text(),
				'description' => '',
				'icon' => 'changes',
				'prioritized' => true,
			];
			return [ $this->getAgentLink(), $viewChangesLink ];
		}
	}

	public function getBodyMessage() {
		if ( $this->event->getExtraParam( 'emailonce' ) && $this->getDistributionType() == 'email' ) {
			return $this->msg( 'notification-body-watchlist-once', $this->getViewingUserForGender() );
		}
		return false;
	}

	private function isMultiUserBundle() {
		foreach ( $this->getBundledEvents() as $bundled ) {
			if ( !$bundled->getAgent()->equals( $this->event->getAgent() ) ) {
				return true;
			}
		}
		return false;
	}

	private function isMultiTypeBundle() {
		foreach ( $this->getBundledEvents() as $bundled ) {
			if ( $bundled->getExtraParam( 'status' ) !== $this->event->getExtraParam( 'status' ) ) {
				return true;
			}
		}
		return false;
	}

	private function getViewChangesUrl() {
		$revid = $this->event->getExtraParam( 'revid' );
		if ( $revid === 0 ) {
			$url = SpecialPage::getTitleFor( 'Log' )->getLocalUrl( [
				'logid' => $this->event->getExtraParam( 'logid' )
			] );
		} else {
			$url = $this->event->getTitle()->getLocalURL( [
				'oldid' => 'prev',
				'diff' => $revid
			] );
		}
		return $url;
	}
}

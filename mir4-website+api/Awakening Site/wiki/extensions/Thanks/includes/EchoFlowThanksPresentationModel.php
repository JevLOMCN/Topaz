<?php

namespace MediaWiki\Extension\Thanks;

use Flow\Notifications\FlowPresentationModel;
use MediaWiki\Language\RawMessage;
use MediaWiki\Title\Title;

class EchoFlowThanksPresentationModel extends FlowPresentationModel {
	public function canRender() {
		return (bool)$this->event->getTitle();
	}

	public function getIconType() {
		return 'thanks';
	}

	public function getHeaderMessage() {
		if ( $this->isBundled() ) {
			$msg = $this->msg( 'notification-bundle-header-flow-thank' );
			$msg->params( $this->getBundleCount() );
			$msg->plaintextParams( $this->getTopicTitle() );
			$msg->params( $this->getViewingUserForGender() );
			return $msg;
		} else {
			// The following message is used here:
			// * notification-header-flow-thank
			$msg = parent::getHeaderMessage();
			$msg->plaintextParams( $this->getTopicTitle() );
			$msg->params( $this->getTruncatedTitleText( $this->event->getTitle(), true ) );
			$msg->params( $this->getViewingUserForGender() );
			return $msg;
		}
	}

	public function getCompactHeaderMessage() {
		// The following message is used here:
		// * notification-compact-header-flow-thank
		$msg = parent::getCompactHeaderMessage();
		$msg->params( $this->getViewingUserForGender() );
		return $msg;
	}

	public function getBodyMessage() {
		$excerpt = $this->event->getExtraParam( 'excerpt' );
		if ( $excerpt ) {
			$msg = new RawMessage( '$1' );
			$msg->plaintextParams( $excerpt );
			return $msg;
		}
	}

	public function getPrimaryLink() {
		$title = Title::makeTitleSafe( NS_TOPIC, $this->event->getExtraParam( 'workflow' ) );
		if ( !$title ) {
			// Workflow IDs that are invalid titles should never happen; we can try
			// falling back on the page title and hope the #flow-post- anchor will be there.
			$title = $this->event->getTitle();
		}
		// Make a link to #flow-post-{postid}
		$title = $title->createFragmentTarget( 'flow-post-' . $this->event->getExtraParam( 'post-id' ) );

		return [
			'url' => $title->getFullURL(),
			'label' => $this->msg( 'notification-link-text-view-post' )->text(),
		];
	}

	public function getSecondaryLinks() {
		if ( $this->isBundled() ) {
			return [ $this->getBoardLink() ];
		} else {
			return [ $this->getAgentLink(), $this->getBoardLink() ];
		}
	}
}

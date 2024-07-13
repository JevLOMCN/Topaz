<?php

namespace MediaWiki\Extension\Notifications\Formatters;

class EchoEmailUserPresentationModel extends EchoEventPresentationModel {

	public function getIconType() {
		return 'emailuser';
	}

	public function getPrimaryLink() {
		return false;
	}

	public function getSecondaryLinks() {
		return [ $this->getAgentLink() ];
	}

	public function getBodyMessage() {
		$preview = $this->event->getExtraParam( 'preview' );
		return $preview ? $this->msg( 'notification-body-emailuser' )->plaintextParams( $preview ) : false;
	}
}

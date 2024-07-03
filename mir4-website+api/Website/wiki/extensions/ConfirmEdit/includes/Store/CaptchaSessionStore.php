<?php

namespace MediaWiki\Extension\ConfirmEdit\Store;

use MediaWiki\Session\SessionManager;

class CaptchaSessionStore extends CaptchaStore {
	protected function __construct() {
		// Make sure the session is started
		SessionManager::getGlobalSession()->persist();
	}

	/**
	 * @inheritDoc
	 */
	public function store( $index, $info ) {
		SessionManager::getGlobalSession()->set( 'captcha' . $index, $info );
	}

	/**
	 * @inheritDoc
	 */
	public function retrieve( $index ) {
		return SessionManager::getGlobalSession()->get( 'captcha' . $index, false );
	}

	/**
	 * @inheritDoc
	 */
	public function clear( $index ) {
		SessionManager::getGlobalSession()->remove( 'captcha' . $index );
	}

	public function cookiesNeeded() {
		return true;
	}
}

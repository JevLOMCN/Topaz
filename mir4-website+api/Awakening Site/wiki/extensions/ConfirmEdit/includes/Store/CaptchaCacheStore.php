<?php

namespace MediaWiki\Extension\ConfirmEdit\Store;

use BagOStuff;
use MediaWiki\MediaWikiServices;

class CaptchaCacheStore extends CaptchaStore {
	/** @var BagOStuff */
	private $store;

	public function __construct() {
		parent::__construct();
		$this->store = MediaWikiServices::getInstance()->getMicroStash();
	}

	/**
	 * @inheritDoc
	 */
	public function store( $index, $info ) {
		global $wgCaptchaSessionExpiration;

		$this->store->set(
			$this->store->makeKey( 'captcha', $index ),
			$info,
			$wgCaptchaSessionExpiration,
			// Assume the write will reach the master DC before the user sends the
			// HTTP POST request attempted to solve the captcha and perform an action
			$this->store::WRITE_BACKGROUND
		);
	}

	/**
	 * @inheritDoc
	 */
	public function retrieve( $index ) {
		return $this->store->get( $this->store->makeKey( 'captcha', $index ) );
	}

	/**
	 * @inheritDoc
	 */
	public function clear( $index ) {
		$this->store->delete( $this->store->makeKey( 'captcha', $index ) );
	}

	public function cookiesNeeded() {
		return false;
	}
}

class_alias( CaptchaCacheStore::class, 'CaptchaCacheStore' );

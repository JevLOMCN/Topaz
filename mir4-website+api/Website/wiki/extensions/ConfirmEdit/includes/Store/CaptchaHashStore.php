<?php

namespace MediaWiki\Extension\ConfirmEdit\Store;

class CaptchaHashStore extends CaptchaStore {
	protected $data = [];

	/**
	 * @inheritDoc
	 */
	public function store( $index, $info ) {
		$this->data[$index] = $info;
	}

	/**
	 * @inheritDoc
	 */
	public function retrieve( $index ) {
		if ( array_key_exists( $index, $this->data ) ) {
			return $this->data[$index];
		}
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function clear( $index ) {
		unset( $this->data[$index] );
	}

	public function cookiesNeeded() {
		return false;
	}

	public function clearAll() {
		$this->data = [];
	}
}

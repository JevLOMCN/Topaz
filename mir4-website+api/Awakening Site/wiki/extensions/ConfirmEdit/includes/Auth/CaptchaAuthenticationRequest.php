<?php

namespace MediaWiki\Extension\ConfirmEdit\Auth;

use MediaWiki\Auth\AuthenticationRequest;
use MediaWiki\Auth\AuthManager;
use MediaWiki\Extension\ConfirmEdit\Hooks;

/**
 * Generic captcha authentication request class. A captcha consist some data stored in the session
 * (e.g. a question and its answer), an ID that references the data, and a solution.
 */
class CaptchaAuthenticationRequest extends AuthenticationRequest {
	/** @var string Identifier of the captcha. Used internally to remember which captcha was used. */
	public $captchaId;

	/** @var array Information about the captcha (e.g. question text; solution). Exact semantics
	 *    differ between types.
	 */
	public $captchaData;

	/** @var string Captcha solution submitted by the user. */
	public $captchaWord;

	/**
	 * @param string $id
	 * @param array $data
	 */
	public function __construct( $id, $data ) {
		$this->captchaId = $id;
		$this->captchaData = $data;
	}

	/** @inheritDoc */
	public function getUniqueId() {
		return 'CaptchaAuthenticationRequest';
	}

	/**
	 * @inheritDoc
	 */
	public function loadFromSubmission( array $data ) {
		$success = parent::loadFromSubmission( $data );
		if ( $success ) {
			// captchaId and captchaWord was set from the submission but captchaData was not.
			$captcha = Hooks::getInstance();
			$this->captchaData = $captcha->retrieveCaptcha( $this->captchaId );
			if ( !$this->captchaData ) {
				return false;
			}
		}
		return $success;
	}

	/**
	 * @inheritDoc
	 */
	public function getFieldInfo() {
		$captcha = Hooks::getInstance();

		// doesn't actually exist but *Captcha::getMessage will handle that
		$action = 'generic';
		switch ( $this->action ) {
			case AuthManager::ACTION_LOGIN:
				$action = 'badlogin';
				break;
			case AuthManager::ACTION_CREATE:
				$action = 'createaccount';
				break;
		}

		$fields = [
			'captchaId' => [
				'type' => 'hidden',
				'value' => $this->captchaId,
				'label' => wfMessage( 'captcha-id-label' ),
				'help' => wfMessage( 'captcha-id-help' ),
			],
			'captchaInfo' => [
				'type' => 'null',
				'label' => $captcha->getMessage( $action ),
				'value' => $captcha->getCaptchaInfo( $this->captchaData, $this->captchaId ),
				'help' => wfMessage( 'captcha-info-help' ),
			],
			'captchaWord' => [
				'type' => 'string',
				'label' => wfMessage( 'captcha-label' ),
				'help' => wfMessage( 'captcha-help' ),
			],
		];

		return $fields;
	}

	/**
	 * @inheritDoc
	 */
	public function getMetadata() {
		return ( Hooks::getInstance() )->describeCaptchaType();
	}

	/**
	 * @param array $data
	 * @return CaptchaAuthenticationRequest
	 */
	public static function __set_state( $data ) {
		$ret = new static( '', [] );
		foreach ( $data as $k => $v ) {
			$ret->$k = $v;
		}
		return $ret;
	}
}

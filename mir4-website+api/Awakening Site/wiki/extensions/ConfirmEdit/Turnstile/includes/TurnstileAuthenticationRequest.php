<?php

namespace MediaWiki\Extension\ConfirmEdit\Turnstile;

use MediaWiki\Auth\AuthenticationRequest;
use MediaWiki\Extension\ConfirmEdit\Auth\CaptchaAuthenticationRequest;

/**
 * Authentication request for Turnstile. Functions almost identically to ReCaptchaNoCaptchaAuthenticationRequest.
 */
class TurnstileAuthenticationRequest extends CaptchaAuthenticationRequest {
	public function __construct() {
		parent::__construct( '', [] );
	}

	/**
	 * @inheritDoc
	 */
	public function loadFromSubmission( array $data ) {
		// unhack the hack in parent
		return AuthenticationRequest::loadFromSubmission( $data );
	}

	/**
	 * @inheritDoc
	 */
	public function getFieldInfo() {
		$fieldInfo = parent::getFieldInfo();

		return [
			'captchaWord' => [
				'type' => 'string',
				'label' => $fieldInfo['captchaInfo']['label'],
				'help' => wfMessage( 'turnstile-help' ),
			],
		];
	}
}

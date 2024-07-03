<?php

namespace MediaWiki\Extension\ConfirmEdit\ReCaptchaNoCaptcha;

use MediaWiki\Auth\AuthenticationRequest;
use MediaWiki\Extension\ConfirmEdit\Auth\CaptchaAuthenticationRequest;

/**
 * Authentication request for ReCaptcha v2. Unlike the parent class, no session storage is used
 * and there is no ID; Google provides a single proof string after successfully solving a captcha.
 */
class ReCaptchaNoCaptchaAuthenticationRequest extends CaptchaAuthenticationRequest {
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
				'help' => wfMessage( 'renocaptcha-help' ),
			],
		];
	}
}

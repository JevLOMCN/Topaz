<?php

namespace MediaWiki\Extension\ConfirmEdit\hCaptcha;

use HTMLFormField;
use MediaWiki\Html\Html;

class HTMLHCaptchaField extends HTMLFormField {
	/** @var string Public key parameter to be passed to hCaptcha. */
	protected $key;

	/** @var string Error returned by hCaptcha in the previous round. */
	protected $error;

	/**
	 * Parameters:
	 * - key: (string, required) Public key
	 * - error: (string) Error from previous round
	 * @param array $params
	 */
	public function __construct( array $params ) {
		$params += [ 'error' => null ];
		parent::__construct( $params );

		$this->key = $params['key'];
		$this->error = $params['error'];

		$this->mName = 'h-captcha-response';
	}

	/**
	 * @inheritDoc
	 */
	public function getInputHTML( $value ) {
		$out = $this->mParent->getOutput();

		$out->addHeadItem(
			'h-captcha',
			"<script src=\"https://hcaptcha.com/1/api.js\" async defer></script>"
		);
		HCaptcha::addCSPSources( $out->getCSP() );
		return Html::element( 'div', [
			'class' => [
				'h-captcha',
				'mw-confirmedit-captcha-fail' => (bool)$this->error,
			],
			'data-sitekey' => $this->key,
		] );
	}
}

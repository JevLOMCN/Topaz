<?php

namespace MediaWiki\Extension\ConfirmEdit\FancyCaptcha;

use ApiBase;

/**
 * Api module to reload FancyCaptcha
 *
 * @ingroup API
 * @ingroup Extensions
 */
class ApiFancyCaptchaReload extends ApiBase {
	public function execute() {
		# Get a new FancyCaptcha form data
		$captcha = new FancyCaptcha();
		$info = $captcha->getCaptcha();
		$captchaIndex = $captcha->storeCaptcha( $info );

		$result = $this->getResult();
		$result->addValue( null, $this->getModuleName(), [ 'index' => $captchaIndex ] );
		return true;
	}

	/** @inheritDoc */
	public function isInternal() {
		return true;
	}

	/** @inheritDoc */
	public function getAllowedParams() {
		return [];
	}

	/**
	 * @see ApiBase::getExamplesMessages()
	 * @return array
	 */
	protected function getExamplesMessages() {
		return [
			'action=fancycaptchareload'
				=> 'apihelp-fancycaptchareload-example-1',
		];
	}
}

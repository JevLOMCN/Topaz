<?php

namespace MediaWiki\Extension\ConfirmEdit\ReCaptchaNoCaptcha;

use HTMLFormField;
use MediaWiki\Html\Html;

/**
 * Creates a ReCaptcha v2 widget. Does not return any data; handling the data submitted by the
 * widget is callers' responsibility.
 */
class HTMLReCaptchaNoCaptchaField extends HTMLFormField {
	/** @var string Public key parameter to be passed to ReCaptcha. */
	protected $key;

	/** @var string Error returned by ReCaptcha in the previous round. */
	protected $error;

	/**
	 * Parameters:
	 * - key: (string, required) ReCaptcha public key
	 * - error: (string) ReCaptcha error from previous round
	 * @param array $params
	 */
	public function __construct( array $params ) {
		$params += [ 'error' => null ];
		parent::__construct( $params );

		$this->key = $params['key'];
		$this->error = $params['error'];

		$this->mName = 'g-recaptcha-response';
	}

	/**
	 * @inheritDoc
	 */
	public function getInputHTML( $value ) {
		$out = $this->mParent->getOutput();
		$lang = htmlspecialchars( urlencode( $this->mParent->getLanguage()->getCode() ) );

		// Insert reCAPTCHA script, in display language, if available.
		// Language falls back to the browser's display language.
		// See https://developers.google.com/recaptcha/docs/faq
		$out->addHeadItem(
			'g-recaptchascript',
			"<script src=\"https://www.recaptcha.net/recaptcha/api.js?hl={$lang}\" async defer></script>"
		);
		ReCaptchaNoCaptcha::addCSPSources( $out->getCSP() );

		$output = Html::element( 'div', [
			'class' => [
				'g-recaptcha',
				'mw-confirmedit-captcha-fail' => (bool)$this->error,
			],
			'data-sitekey' => $this->key,
		] );
		$htmlUrlencoded = htmlspecialchars( urlencode( $this->key ) );
		$output .= <<<HTML
<noscript>
  <div>
    <div style="width: 302px; height: 422px; position: relative;">
      <div style="width: 302px; height: 422px; position: absolute;">
        <iframe
        	src="https://www.recaptcha.net/recaptcha/api/fallback?k={$htmlUrlencoded}&hl={$lang}"
            frameborder="0" scrolling="no"
            style="width: 302px; height:422px; border-style: none;">
        </iframe>
      </div>
    </div>
    <div style="width: 300px; height: 60px; border-style: none;
                bottom: 12px; left: 25px; margin: 0px; padding: 0px; right: 25px;
                background: #f9f9f9; border: 1px solid #c1c1c1; border-radius: 3px;">
      <textarea id="g-recaptcha-response" name="g-recaptcha-response"
                class="g-recaptcha-response"
                style="width: 250px; height: 40px; border: 1px solid #c1c1c1;
                       margin: 10px 25px; padding: 0px; resize: none;" >
      </textarea>
    </div>
  </div>
</noscript>
HTML;
		return $output;
	}
}

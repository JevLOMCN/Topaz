<?php

namespace MediaWiki\Extension\ConfirmEdit\Turnstile;

use ApiBase;
use FormatJson;
use Html;
use MediaWiki\Auth\AuthenticationRequest;
use MediaWiki\Extension\ConfirmEdit\Auth\CaptchaAuthenticationRequest;
use MediaWiki\Extension\ConfirmEdit\Hooks;
use MediaWiki\Extension\ConfirmEdit\SimpleCaptcha\SimpleCaptcha;
use MediaWiki\Language\RawMessage;
use MediaWiki\MediaWikiServices;
use Message;
use Status;
use WebRequest;

class Turnstile extends SimpleCaptcha {
	// used for turnstile-edit, turnstile-addurl, turnstile-badlogin, turnstile-createaccount,
	// turnstile-create, turnstile-sendemail via getMessage()
	protected static $messagePrefix = 'turnstile-';

	private $error = null;

	/**
	 * Get the captcha form.
	 * @param int $tabIndex
	 * @return array
	 */
	public function getFormInformation( $tabIndex = 1 ) {
		global $wgTurnstileSiteKey, $wgLang;
		$lang = htmlspecialchars( urlencode( $wgLang->getCode() ) );

		$output = Html::element( 'div', [
			'class' => [
				'cf-turnstile',
				'mw-confirmedit-captcha-fail' => (bool)$this->error,
			],
			'data-sitekey' => $wgTurnstileSiteKey
		] );
		return [
			'html' => $output,
			'headitems' => [
				// Insert Turnstile script, in display language, if available.
				// Language falls back to the browser's display language.
				// See https://developers.cloudflare.com/turnstile/reference/supported-languages/
				"<script src=\"https://challenges.cloudflare.com/turnstile/v0/api.js?language={$lang}\" async defer>
				</script>"
			]
		];
	}

	/**
	 * @return string[]
	 */
	public static function getCSPUrls() {
		return [ 'https://challenges.cloudflare.com/turnstile/v0/api.js' ];
	}

	/**
	 * @param Status|array|string $info
	 */
	protected function logCheckError( $info ) {
		if ( $info instanceof Status ) {
			$errors = $info->getErrorsArray();
			$error = $errors[0][0];
		} elseif ( is_array( $info ) ) {
			$error = implode( ',', $info );
		} else {
			$error = $info;
		}

		wfDebugLog( 'captcha', 'Unable to validate response: ' . $error );
	}

	/**
	 * @param WebRequest $request
	 * @return array
	 */
	protected function getCaptchaParamsFromRequest( WebRequest $request ) {
		// Turnstile combines captcha ID + solution into a single value
		// API is hardwired to return captchaWord, so use that if the standard isempty
		// "captchaWord" is sent as "captchaword" by visual editor
		$index = 'not used';
		$response = $request->getVal(
			'cf-turnstile-response',
			$request->getVal( 'captchaWord', $request->getVal( 'captchaword' ) )
		);
		return [ $index, $response ];
	}

	/**
	 * Check if the user solved the captcha.
	 *
	 * Based on reference implementation:
	 * https://developers.cloudflare.com/turnstile/get-started/server-side-validation/
	 *
	 * @param mixed $_ Not used
	 * @param string $word captcha solution
	 * @return bool
	 */
	protected function passCaptcha( $_, $word ) {
		global $wgRequest, $wgTurnstileSecretKey, $wgTurnstileSendRemoteIP;

		$url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
		// Build data to append to request
		$data = [
			'secret' => $wgTurnstileSecretKey,
			'response' => $word,
		];
		if ( $wgTurnstileSendRemoteIP ) {
			$data['remoteip'] = $wgRequest->getIP();
		}
		$request = MediaWikiServices::getInstance()->getHttpRequestFactory()
			->create( $url, [ 'method' => 'POST' ], __METHOD__ );
		$request->setData( $data );
		$status = $request->execute();
		if ( !$status->isOK() ) {
			$this->error = 'http';
			$this->logCheckError( $status );
			return false;
		}
		$response = FormatJson::decode( $request->getContent(), true );
		if ( !$response ) {
			$this->error = 'json';
			$this->logCheckError( $this->error );
			return false;
		}
		// Turnstile always returns the "error-codes" array, so we should just
		// check whether it is empty or not.
		if ( !empty( $response['error-codes'] ) ) {
			$this->error = 'turnstile-api';
			$this->logCheckError( $response['error-codes'] );
			return false;
		}

		return $response['success'];
	}

	/**
	 * @param array &$resultArr
	 */
	protected function addCaptchaAPI( &$resultArr ) {
		$resultArr['captcha'] = $this->describeCaptchaType();
		$resultArr['captcha']['error'] = $this->error;
	}

	/**
	 * @return array
	 */
	public function describeCaptchaType() {
		global $wgTurnstileSiteKey;
		return [
			'type' => 'turnstile',
			'mime' => 'application/javascript',
			'key' => $wgTurnstileSiteKey,
		];
	}

	/**
	 * Show a message asking the user to enter a captcha on edit
	 * The result will be treated as wiki text
	 *
	 * @param string $action Action being performed
	 * @return Message
	 */
	public function getMessage( $action ) {
		$msg = parent::getMessage( $action );
		if ( $this->error ) {
			$msg = new RawMessage( '<div class="error">$1</div>', [ $msg ] );
		}
		return $msg;
	}

	/**
	 * @param ApiBase $module
	 * @param array &$params
	 * @param int $flags
	 * @return bool
	 */
	public function apiGetAllowedParams( ApiBase $module, &$params, $flags ) {
		if ( $flags && $this->isAPICaptchaModule( $module ) ) {
			$params['cf-turnstile-response'] = [
				ApiBase::PARAM_HELP_MSG => 'turnstile-apihelp-param-cf-turnstile-response',
			];
		}

		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function getError() {
		return $this->error;
	}

	/**
	 * @inheritDoc
	 */
	public function storeCaptcha( $info ) {
		return 'not used';
	}

	/**
	 * @inheritDoc
	 */
	public function retrieveCaptcha( $index ) {
		// just pretend it worked
		return [ 'index' => $index ];
	}

	/**
	 * @inheritDoc
	 */
	public function getCaptcha() {
		// Turnstile is handled by frontend code + an external provider; nothing to do here.
		return [];
	}

	/**
	 * @return TurnstileAuthenticationRequest
	 */
	public function createAuthenticationRequest() {
		return new TurnstileAuthenticationRequest();
	}

	/**
	 * @param array $requests
	 * @param array $fieldInfo
	 * @param array &$formDescriptor
	 * @param string $action
	 */
	public function onAuthChangeFormFields(
		array $requests, array $fieldInfo, array &$formDescriptor, $action
	) {
		global $wgTurnstileSiteKey;

		$req = AuthenticationRequest::getRequestByClass( $requests,
			CaptchaAuthenticationRequest::class, true );
		if ( !$req ) {
			return;
		}

		// ugly way to retrieve error information
		$captcha = Hooks::getInstance();

		$formDescriptor['captchaWord'] = [
			'class' => HTMLTurnstileField::class,
			'key' => $wgTurnstileSiteKey,
			'error' => $captcha->getError(),
		] + $formDescriptor['captchaWord'];
	}
}

<?php

namespace MediaWiki\Extension\ConfirmEdit\hCaptcha;

use ApiBase;
use FormatJson;
use MediaWiki\Auth\AuthenticationRequest;
use MediaWiki\Extension\ConfirmEdit\Auth\CaptchaAuthenticationRequest;
use MediaWiki\Extension\ConfirmEdit\Hooks;
use MediaWiki\Extension\ConfirmEdit\SimpleCaptcha\SimpleCaptcha;
use MediaWiki\Html\Html;
use MediaWiki\Language\RawMessage;
use MediaWiki\MediaWikiServices;
use MediaWiki\Request\ContentSecurityPolicy;
use MediaWiki\Request\WebRequest;
use MediaWiki\Status\Status;
use Message;
use RequestContext;

class HCaptcha extends SimpleCaptcha {
	// used for hcaptcha-edit, hcaptcha-addurl, hcaptcha-badlogin, hcaptcha-createaccount,
	// hcaptcha-create, hcaptcha-sendemail via getMessage()
	protected static $messagePrefix = 'hcaptcha-';

	private $error = null;

	private $hCaptchaConfig;

	private $siteKey;

	public function __construct() {
		$this->hCaptchaConfig = MediaWikiServices::getInstance()->getConfigFactory()
			->makeConfig( 'hcaptcha' );
		$this->siteKey = $this->hCaptchaConfig->get( 'HCaptchaSiteKey' );
	}

	/**
	 * Get the captcha form.
	 * @param int $tabIndex
	 * @return array
	 */
	public function getFormInformation( $tabIndex = 1 ) {
		$output = Html::element( 'div', [
			'class' => [
				'h-captcha',
				'mw-confirmedit-captcha-fail' => (bool)$this->error,
			],
			'data-sitekey' => $this->siteKey
		] );

		return [
			'html' => $output,
			'headitems' => [
				"<script src=\"https://hcaptcha.com/1/api.js\" async defer></script>"
			]
		];
	}

	/**
	 * @return string[]
	 */
	public static function getCSPUrls() {
		return [ 'https://hcaptcha.com', 'https://*.hcaptcha.com' ];
	}

	/**
	 * Adds the CSP policies necessary for the captcha module to work in a CSP enforced
	 * setup.
	 *
	 * @param ContentSecurityPolicy $csp The CSP instance to add the policies to, usually
	 * obtained from {@link OutputPage::getCSP()}
	 */
	public static function addCSPSources( ContentSecurityPolicy $csp ) {
		foreach ( static::getCSPUrls() as $src ) {
			// Since frame-src is not supported
			$csp->addDefaultSrc( $src );
			$csp->addScriptSrc( $src );
			$csp->addStyleSrc( $src );
		}
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

		\wfDebugLog( 'captcha', 'Unable to validate response: ' . $error );
	}

	/**
	 * @param WebRequest $request
	 * @return array
	 */
	protected function getCaptchaParamsFromRequest( WebRequest $request ) {
		$response = $request->getVal(
			'h-captcha-response',
			$request->getVal( 'captchaWord', $request->getVal( 'captchaword' ) )
		);
		return [ '', $response ];
	}

	/**
	 * Check, if the user solved the captcha.
	 *
	 * Based on reference implementation:
	 * https://github.com/google/recaptcha#php and https://docs.hcaptcha.com/
	 *
	 * @param mixed $_ Not used
	 * @param string $token token from the POST data
	 * @return bool
	 */
	protected function passCaptcha( $_, $token ) {
		$webRequest = RequestContext::getMain()->getRequest();

		$secretKey = $this->hCaptchaConfig->get( 'HCaptchaSecretKey' );
		$sendRemoteIp = $this->hCaptchaConfig->get( 'HCaptchaSendRemoteIP' );
		$proxy = $this->hCaptchaConfig->get( 'HCaptchaProxy' );

		$url = 'https://hcaptcha.com/siteverify';
		$data = [
			'secret' => $secretKey,
			'response' => $token,
		];
		if ( $sendRemoteIp ) {
			$data['remoteip'] = $webRequest->getIP();
		}

		$options = [
			'method' => 'POST',
			'postData' => $data,
		];

		if ( $proxy ) {
			$options['proxy'] = $proxy;
		}

		$request = MediaWikiServices::getInstance()->getHttpRequestFactory()
			->create( $url, $options, __METHOD__ );

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
		if ( isset( $response['error-codes'] ) ) {
			$this->error = 'hcaptcha-api';
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
		return [
			'type' => 'hcaptcha',
			'mime' => 'application/javascript',
			'key' => $this->siteKey,
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
		// hCaptcha is stored externally, the ID will be generated at that time as well, and
		// the one returned here won't be used. Just pretend this worked.
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
		// hCaptcha is handled by frontend code + an external provider; nothing to do here.
		return [];
	}

	/**
	 * @return HCaptchaAuthenticationRequest
	 */
	public function createAuthenticationRequest() {
		return new HCaptchaAuthenticationRequest();
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
		$req = AuthenticationRequest::getRequestByClass(
			$requests,
			CaptchaAuthenticationRequest::class,
			true
		);
		if ( !$req ) {
			return;
		}

		// ugly way to retrieve error information
		$captcha = Hooks::getInstance();

		$formDescriptor['captchaWord'] = [
			'class' => HTMLHCaptchaField::class,
			'key' => $this->siteKey,
			'error' => $captcha->getError(),
		] + $formDescriptor['captchaWord'];
	}
}

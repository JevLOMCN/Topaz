<?php

use MediaWiki\Auth\AuthenticationRequestTestCase;
use MediaWiki\Extension\ConfirmEdit\Auth\CaptchaAuthenticationRequest;
use MediaWiki\Extension\ConfirmEdit\SimpleCaptcha\SimpleCaptcha;
use MediaWiki\Extension\ConfirmEdit\Store\CaptchaHashStore;
use MediaWiki\Extension\ConfirmEdit\Store\CaptchaStore;

/**
 * @covers \MediaWiki\Extension\ConfirmEdit\Auth\CaptchaAuthenticationRequest
 */
class CaptchaAuthenticationRequestTest extends AuthenticationRequestTestCase {
	public function setUp(): void {
		parent::setUp();
		$this->setMwGlobals( [
			'wgCaptchaClass' => SimpleCaptcha::class,
			'wgCaptchaStorageClass' => CaptchaHashStore::class,
		] );
		CaptchaStore::unsetInstanceForTests();
		CaptchaStore::get()->clearAll();
		CaptchaStore::get()->store( '345', [ 'question' => '2+2', 'answer' => '4' ] );
	}

	protected function getInstance( array $args = [] ) {
		return new CaptchaAuthenticationRequest( $args[0], $args[1] );
	}

	public static function provideGetFieldInfo() {
		return [
			[ [ '123', [ 'question' => '1+2', 'answer' => '3' ] ] ],
		];
	}

	public static function provideLoadFromSubmission() {
		return [
			'no id' => [
				[ '123', [ 'question' => '1+2', 'answer' => '3' ] ],
				[],
				false,
			],
			'no answer' => [
				[ '123', [ 'question' => '1+2', 'answer' => '3' ] ],
				[ 'captchaId' => '345' ],
				false,
			],
			'missing' => [
				[ '123', [ 'question' => '1+2', 'answer' => '3' ] ],
				[ 'captchaId' => '234', 'captchaWord' => '5' ],
				false,
			],
			'normal' => [
				[ '123', [ 'question' => '1+2', 'answer' => '3' ] ],
				[ 'captchaId' => '345', 'captchaWord' => '5' ],
				[ 'captchaId' => '345', 'captchaData' => [ 'question' => '2+2', 'answer' => '4' ],
					'captchaWord' => '5' ],
			],
		];
	}
}

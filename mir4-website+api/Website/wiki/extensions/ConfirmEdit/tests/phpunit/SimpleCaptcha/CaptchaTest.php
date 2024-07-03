<?php

use MediaWiki\Config\Config;
use MediaWiki\Extension\ConfirmEdit\CaptchaTriggers;
use MediaWiki\Extension\ConfirmEdit\SimpleCaptcha\SimpleCaptcha;
use MediaWiki\Request\WebRequest;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use Wikimedia\ScopedCallback;

/**
 * @covers \MediaWiki\Extension\ConfirmEdit\SimpleCaptcha\SimpleCaptcha
 */
class CaptchaTest extends MediaWikiIntegrationTestCase {

	/** @var ScopedCallback[] */
	private $hold = [];

	public function tearDown(): void {
		// Destroy any ScopedCallbacks being held
		$this->hold = [];
		parent::tearDown();
	}

	/**
	 * @dataProvider provideSimpleTriggersCaptcha
	 */
	public function testTriggersCaptcha( $action, $expectedResult ) {
		$captcha = new SimpleCaptcha();
		$this->setMwGlobals( [
			'wgCaptchaTriggers' => [
				$action => $expectedResult,
			]
		] );
		$this->assertEquals( $expectedResult, $captcha->triggersCaptcha( $action ) );
	}

	public static function provideSimpleTriggersCaptcha() {
		$data = [];
		$captchaTriggers = new ReflectionClass( CaptchaTriggers::class );
		$constants = $captchaTriggers->getConstants();
		foreach ( $constants as $const ) {
			$data[] = [ $const, true ];
			$data[] = [ $const, false ];
		}
		return $data;
	}

	/**
	 * @dataProvider provideNamespaceOverwrites
	 */
	public function testNamespaceTriggersOverwrite( $trigger, $expected ) {
		$captcha = new SimpleCaptcha();
		$this->setMwGlobals( [
			'wgCaptchaTriggers' => [
				$trigger => !$expected,
			],
			'wgCaptchaTriggersOnNamespace' => [
				0 => [
					$trigger => $expected,
				],
			],
		] );
		$title = Title::newFromText( 'Main' );
		$this->assertEquals( $expected, $captcha->triggersCaptcha( $trigger, $title ) );
	}

	public static function provideNamespaceOverwrites() {
		return [
			[ 'edit', true ],
			[ 'edit', false ],
		];
	}

	private function setCaptchaTriggersAttribute( $trigger, $value ) {
		// Avoid clobbering captcha triggers registered by other extensions
		$this->setMwGlobals( 'wgCaptchaTriggers', $GLOBALS['wgCaptchaTriggers'] );

		$this->hold[] = ExtensionRegistry::getInstance()->setAttributeForTest(
			'CaptchaTriggers', [ $trigger => $value ]
		);
	}

	/**
	 * @dataProvider provideAttributeSet
	 */
	public function testCaptchaTriggersAttributeSetTrue( $trigger, $value ) {
		$this->setCaptchaTriggersAttribute( $trigger, $value );
		$captcha = new SimpleCaptcha();
		$this->assertEquals( $value, $captcha->triggersCaptcha( $trigger ) );
	}

	public static function provideAttributeSet() {
		return [
			[ 'test', true ],
			[ 'test', false ],
		];
	}

	/**
	 * @dataProvider provideAttributeOverwritten
	 */
	public function testCaptchaTriggersAttributeGetsOverwritten( $trigger, $expected ) {
		$this->setMwGlobals( 'wgCaptchaTriggers', [ $trigger => $expected ] );
		$this->setCaptchaTriggersAttribute( $trigger, !$expected );
		$captcha = new SimpleCaptcha();
		$this->assertEquals( $expected, $captcha->triggersCaptcha( $trigger ) );
	}

	public static function provideAttributeOverwritten() {
		return [
			[ 'edit', true ],
			[ 'edit', false ],
		];
	}

	/**
	 * @dataProvider provideCanSkipCaptchaUserright
	 */
	public function testCanSkipCaptchaUserright( $userIsAllowed, $expected ) {
		$testObject = new SimpleCaptcha();
		$user = $this->createMock( User::class );
		$user->method( 'isAllowed' )->willReturn( $userIsAllowed );

		$actual = $testObject->canSkipCaptcha( $user, RequestContext::getMain()->getConfig() );

		$this->assertEquals( $expected, $actual );
	}

	public static function provideCanSkipCaptchaUserright() {
		return [
			[ true, true ],
			[ false, false ]
		];
	}

	/**
	 * @dataProvider provideCanSkipCaptchaMailconfirmed
	 */
	public function testCanSkipCaptchaMailconfirmed( $allowUserConfirmEmail,
		$userIsMailConfirmed, $expected ) {
		$testObject = new SimpleCaptcha();
		$user = $this->createMock( User::class );
		$user->method( 'isEmailConfirmed' )->willReturn( $userIsMailConfirmed );
		$config = $this->createMock( Config::class );
		$config->method( 'get' )->willReturn( $allowUserConfirmEmail );

		$actual = $testObject->canSkipCaptcha( $user, $config );

		$this->assertEquals( $expected, $actual );
	}

	public static function provideCanSkipCaptchaMailconfirmed() {
		return [
			[ false, false, false ],
			[ false, true, false ],
			[ true, false, false ],
			[ true, true, true ],
		];
	}

	/**
	 * @dataProvider provideCanSkipCaptchaIPWhitelisted
	 */
	public function testCanSkipCaptchaIPWhitelisted( $requestIP, $IPWhitelist, $expected ) {
		$testObject = new SimpleCaptcha();
		$config = $this->createMock( Config::class );
		$request = $this->createMock( WebRequest::class );
		$request->method( 'getIP' )->willReturn( $requestIP );

		$this->setMwGlobals( [
			'wgRequest' => $request,
			'wgCaptchaWhitelistIP' => $IPWhitelist
		] );

		$actual = $testObject->canSkipCaptcha( RequestContext::getMain()->getUser(), $config );

		$this->assertEquals( $expected, $actual );
	}

	public static function provideCanSkipCaptchaIPWhitelisted() {
		return ( [
			[ '127.0.0.1', [ '127.0.0.1', '127.0.0.2' ], true ],
			[ '127.0.0.1', [], false ]
		]
		);
	}
}

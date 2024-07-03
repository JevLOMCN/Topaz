<?php

use MediaWiki\Auth\AuthManager;
use MediaWiki\Auth\UsernameAuthenticationRequest;
use MediaWiki\Extension\ConfirmEdit\Auth\CaptchaAuthenticationRequest;
use MediaWiki\Extension\ConfirmEdit\Auth\CaptchaPreAuthenticationProvider;
use MediaWiki\Extension\ConfirmEdit\Auth\LoginAttemptCounter;
use MediaWiki\Extension\ConfirmEdit\Hooks;
use MediaWiki\Extension\ConfirmEdit\SimpleCaptcha\SimpleCaptcha;
use MediaWiki\Extension\ConfirmEdit\Store\CaptchaHashStore;
use MediaWiki\Extension\ConfirmEdit\Store\CaptchaStore;
use MediaWiki\Request\FauxRequest;
use MediaWiki\Tests\Unit\Auth\AuthenticationProviderTestTrait;
use MediaWiki\User\User;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \MediaWiki\Extension\ConfirmEdit\Auth\CaptchaPreAuthenticationProvider
 * @group Database
 */
class CaptchaPreAuthenticationProviderTest extends MediaWikiIntegrationTestCase {
	use AuthenticationProviderTestTrait;

	public function setUp(): void {
		parent::setUp();
		$this->setMwGlobals( [
			'wgCaptchaClass' => SimpleCaptcha::class,
			'wgCaptchaBadLoginAttempts' => 1,
			'wgCaptchaBadLoginPerUserAttempts' => 1,
			'wgCaptchaStorageClass' => CaptchaHashStore::class,
		] );
		CaptchaStore::unsetInstanceForTests();
		CaptchaStore::get()->clearAll();
	}

	public function tearDown(): void {
		parent::tearDown();
		// make sure $wgCaptcha resets between tests
		TestingAccessWrapper::newFromClass( Hooks::class )->instanceCreated = false;
	}

	/**
	 * @dataProvider provideGetAuthenticationRequests
	 */
	public function testGetAuthenticationRequests(
		$action, $useExistingUserOrNull, $triggers, $needsCaptcha, $preTestCallback = null
	) {
		if ( $useExistingUserOrNull === true ) {
			$username = $this->getTestSysop()->getUserIdentity()->getName();
		} elseif ( $useExistingUserOrNull === false ) {
			$username = 'Foo';
		} else {
			$username = null;
		}
		$this->setTriggers( $triggers );
		if ( $preTestCallback ) {
			$fn = array_shift( $preTestCallback );
			call_user_func_array( [ $this, $fn ], $preTestCallback );
		}

		/** @var FauxRequest $request */
		$request = RequestContext::getMain()->getRequest();
		$request->setCookie( 'UserName', $username );

		$provider = new CaptchaPreAuthenticationProvider();
		$this->initProvider( $provider, null, null, $this->getServiceContainer()->getAuthManager() );
		$reqs = $provider->getAuthenticationRequests( $action, [ 'username' => $username ] );
		if ( $needsCaptcha ) {
			$this->assertCount( 1, $reqs );
			$this->assertInstanceOf( CaptchaAuthenticationRequest::class, $reqs[0] );
		} else {
			$this->assertSame( [], $reqs );
		}
	}

	public static function provideGetAuthenticationRequests() {
		return [
			[ AuthManager::ACTION_LOGIN, null, [], false ],
			[ AuthManager::ACTION_LOGIN, null, [ 'badlogin' ], false ],
			[ AuthManager::ACTION_LOGIN, null, [ 'badlogin' ], true, [ 'blockLogin', 'Foo' ] ],
			[ AuthManager::ACTION_LOGIN, null, [ 'badloginperuser' ], false, [ 'blockLogin', 'Foo' ] ],
			[ AuthManager::ACTION_LOGIN, false, [ 'badloginperuser' ], false, [ 'blockLogin', 'Bar' ] ],
			[ AuthManager::ACTION_LOGIN, false, [ 'badloginperuser' ], true, [ 'blockLogin', 'Foo' ] ],
			[ AuthManager::ACTION_LOGIN, null, [ 'badloginperuser' ], true, [ 'flagSession' ] ],
			[ AuthManager::ACTION_CREATE, null, [], false ],
			[ AuthManager::ACTION_CREATE, null, [ 'createaccount' ], true ],
			[ AuthManager::ACTION_CREATE, true, [ 'createaccount' ], false ],
			[ AuthManager::ACTION_LINK, null, [], false ],
			[ AuthManager::ACTION_CHANGE, null, [], false ],
			[ AuthManager::ACTION_REMOVE, null, [], false ],
		];
	}

	public function testGetAuthenticationRequests_store() {
		$this->setTriggers( [ 'createaccount' ] );
		$captcha = new SimpleCaptcha();
		$provider = new CaptchaPreAuthenticationProvider();
		$this->initProvider( $provider, null, null, $this->getServiceContainer()->getAuthManager() );

		$reqs = $provider->getAuthenticationRequests( AuthManager::ACTION_CREATE,
			[ 'username' => 'Foo' ] );

		$this->assertCount( 1, $reqs );
		$this->assertInstanceOf( CaptchaAuthenticationRequest::class, $reqs[0] );

		$id = $reqs[0]->captchaId;
		$data = TestingAccessWrapper::newFromObject( $reqs[0] )->captchaData;
		$this->assertEquals( $captcha->retrieveCaptcha( $id ), $data + [ 'index' => $id ] );
	}

	/**
	 * @dataProvider provideTestForAuthentication
	 */
	public function testTestForAuthentication( $req, $isBadLoginTriggered,
		$isBadLoginPerUserTriggered, $result
	) {
		$this->setTemporaryHook( 'PingLimiter', static function ( $user, $action, &$result ) {
			$result = false;
			return false;
		} );
		CaptchaStore::get()->store( '345', [ 'question' => '2+2', 'answer' => '4' ] );
		$loginAttemptCounter = $this->getMockBuilder( LoginAttemptCounter::class )
			->onlyMethods( [ 'isBadLoginTriggered', 'isBadLoginPerUserTriggered' ] )
			->disableOriginalConstructor()
			->getMock();
		$loginAttemptCounter->expects( $this->any() )->method( 'isBadLoginTriggered' )
			->willReturn( $isBadLoginTriggered );
		$loginAttemptCounter->expects( $this->any() )->method( 'isBadLoginPerUserTriggered' )
			->willReturn( $isBadLoginPerUserTriggered );
		$provider = $this->getProvider();
		$provider->loginAttemptCounter = $loginAttemptCounter;
		$this->initProvider( $provider, null, null, $this->getServiceContainer()->getAuthManager() );

		$status = $provider->testForAuthentication( $req ? [ $req ] : [] );

		$this->assertEquals( $result, $status->isGood() );
	}

	public static function provideTestForAuthentication() {
		$fallback = new UsernameAuthenticationRequest();
		$fallback->username = 'Foo';
		return [
			// [ auth request, bad login?, bad login per user?, result ]
			'no need to check' => [ $fallback, false, false, true ],
			'badlogin' => [ $fallback, true, false, false ],
			'badloginperuser, no username' => [ null, false, true, true ],
			'badloginperuser' => [ $fallback, false, true, false ],
			'non-existent captcha' => [ self::getCaptchaRequest( '123', '4' ), true, true, false ],
			'wrong captcha' => [ self::getCaptchaRequest( '345', '6' ), true, true, false ],
			'correct captcha' => [ self::getCaptchaRequest( '345', '4' ), true, true, true ],
		];
	}

	/**
	 * @dataProvider provideTestForAccountCreation
	 */
	public function testTestForAccountCreation( $req, $creatorIsSysop, $result, $disableTrigger = false ) {
		$this->setTemporaryHook( 'PingLimiter', static function ( $user, $action, &$result ) {
			$result = false;
			return false;
		} );
		$this->setTriggers( $disableTrigger ? [] : [ 'createaccount' ] );
		CaptchaStore::get()->store( '345', [ 'question' => '2+2', 'answer' => '4' ] );
		$user = User::newFromName( 'Foo' );
		$provider = new CaptchaPreAuthenticationProvider();
		$this->initProvider( $provider, null, null, $this->getServiceContainer()->getAuthManager() );

		$creator = $creatorIsSysop ? $this->getTestSysop()->getUser() : User::newFromName( 'Bar' );
		$status = $provider->testForAccountCreation( $user, $creator, $req ? [ $req ] : [] );
		$this->assertEquals( $result, $status->isGood() );
	}

	public static function provideTestForAccountCreation() {
		return [
			// [ auth request, creator, result, disable trigger? ]
			'no captcha' => [ null, false, false ],
			'non-existent captcha' => [ self::getCaptchaRequest( '123', '4' ), false, false ],
			'wrong captcha' => [ self::getCaptchaRequest( '345', '6' ), false, false ],
			'correct captcha' => [ self::getCaptchaRequest( '345', '4' ), false, true ],
			'user is exempt' => [ null, true, true ],
			'disabled' => [ null, false, true, 'disable' ],
		];
	}

	public function testPostAuthentication() {
		$this->setTriggers( [ 'badlogin', 'badloginperuser' ] );
		$captcha = new SimpleCaptcha();
		$user = User::newFromName( 'Foo' );
		$anotherUser = User::newFromName( 'Bar' );
		$provider = $this->getProvider();
		$loginAttemptCounter = new LoginAttemptCounter( $captcha );
		$provider->loginAttemptCounter = $loginAttemptCounter;
		$this->initProvider( $provider, null, null, $this->getServiceContainer()->getAuthManager() );

		$this->assertFalse( $loginAttemptCounter->isBadLoginTriggered() );
		$this->assertFalse( $loginAttemptCounter->isBadLoginPerUserTriggered( $user ) );

		$provider->postAuthentication( $user, \MediaWiki\Auth\AuthenticationResponse::newFail(
			wfMessage( '?' ) ) );

		$this->assertTrue( $loginAttemptCounter->isBadLoginTriggered() );
		$this->assertTrue( $loginAttemptCounter->isBadLoginPerUserTriggered( $user ) );
		$this->assertFalse( $loginAttemptCounter->isBadLoginPerUserTriggered( $anotherUser ) );

		$provider->postAuthentication( $user, \MediaWiki\Auth\AuthenticationResponse::newPass( 'Foo' ) );

		$this->assertFalse( $loginAttemptCounter->isBadLoginPerUserTriggered( $user ) );
	}

	public function testPostAuthentication_disabled() {
		$this->setTriggers( [] );
		$captcha = new SimpleCaptcha();
		$loginAttemptCounter = new LoginAttemptCounter( $captcha );
		$user = User::newFromName( 'Foo' );
		$provider = $this->getProvider();
		$provider->loginAttemptCounter = $loginAttemptCounter;
		$this->initProvider( $provider, null, null, $this->getServiceContainer()->getAuthManager() );

		$this->assertFalse( $loginAttemptCounter->isBadLoginTriggered() );
		$this->assertFalse( $loginAttemptCounter->isBadLoginPerUserTriggered( $user ) );

		$provider->postAuthentication( $user, \MediaWiki\Auth\AuthenticationResponse::newFail(
			wfMessage( '?' ) ) );

		$this->assertFalse( $loginAttemptCounter->isBadLoginTriggered() );
		$this->assertFalse( $loginAttemptCounter->isBadLoginPerUserTriggered( $user ) );
	}

	/**
	 * @dataProvider providePingLimiter
	 */
	public function testPingLimiter( array $attempts ) {
		$this->mergeMwGlobalArrayValue(
			'wgRateLimits',
			[
				'badcaptcha' => [
					'user' => [ 1, 1 ],
				],
			]
		);
		$provider = new CaptchaPreAuthenticationProvider();
		$this->initProvider( $provider, null, null, $this->getServiceContainer()->getAuthManager() );
		$providerAccess = TestingAccessWrapper::newFromObject( $provider );

		$disablePingLimiter = false;
		$this->setTemporaryHook( 'PingLimiter',
			static function ( &$user, $action, &$result ) use ( &$disablePingLimiter ) {
				if ( $disablePingLimiter ) {
					$result = false;
					return false;
				}
				return null;
			}
		);
		foreach ( $attempts as $attempt ) {
			$disablePingLimiter = !empty( $attempts[3] );
			$captcha = new SimpleCaptcha();
			CaptchaStore::get()->store( '345', [ 'question' => '7+7', 'answer' => '14' ] );
			$success = $providerAccess->verifyCaptcha( $captcha, [ $attempts[0] ], $attempts[1] );
			$this->assertEquals( $attempts[2], $success );
		}
	}

	public static function providePingLimiter() {
		$sysop = User::newFromName( 'UTSysop' );
		return [
			// sequence of [ auth request, user, result, disable ping limiter? ]
			'no failure' => [
				[ self::getCaptchaRequest( '345', '14' ), new User(), true ],
				[ self::getCaptchaRequest( '345', '14' ), new User(), true ],
			],
			'limited' => [
				[ self::getCaptchaRequest( '345', '33' ), new User(), false ],
				[ self::getCaptchaRequest( '345', '14' ), new User(), false ],
			],
			'exempt user' => [
				[ self::getCaptchaRequest( '345', '33' ), $sysop, false ],
				[ self::getCaptchaRequest( '345', '14' ), $sysop, true ],
			],
			'pinglimiter disabled' => [
				[ self::getCaptchaRequest( '345', '33' ), new User(), false, 'disable' ],
				[ self::getCaptchaRequest( '345', '14' ), new User(), true, 'disable' ],
			],
		];
	}

	protected static function getCaptchaRequest( $id, $word, $username = null ) {
		$req = new CaptchaAuthenticationRequest( $id, [ 'question' => '?', 'answer' => $word ] );
		$req->captchaWord = $word;
		$req->username = $username;
		return $req;
	}

	protected function blockLogin( $username ) {
		$counter = new LoginAttemptCounter( new SimpleCaptcha() );
		$counter->increaseBadLoginCounter( $username );
	}

	protected function flagSession() {
		RequestContext::getMain()->getRequest()->getSession()
			->set( 'ConfirmEdit:loginCaptchaPerUserTriggered', true );
	}

	protected function setTriggers( $triggers ) {
		$types = [ 'edit', 'create', 'sendemail', 'addurl', 'createaccount', 'badlogin',
			'badloginperuser' ];
		$captchaTriggers = array_combine( $types, array_map( static function ( $type ) use ( $triggers ) {
			return in_array( $type, $triggers, true );
		}, $types ) );
		$this->setMwGlobals( 'wgCaptchaTriggers', $captchaTriggers );
	}

	private function getProvider(): CaptchaPreAuthenticationProvider {
		return new class() extends CaptchaPreAuthenticationProvider {
			public ?LoginAttemptCounter $loginAttemptCounter = null;

			protected function getLoginAttemptCounter( SimpleCaptcha $captcha ): LoginAttemptCounter {
				return $this->loginAttemptCounter ?: parent::getLoginAttemptCounter( $captcha );
			}
		};
	}

}

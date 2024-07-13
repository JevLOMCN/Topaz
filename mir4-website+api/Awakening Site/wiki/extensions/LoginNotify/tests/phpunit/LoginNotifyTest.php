<?php

use LoginNotify\LoginNotify;
use MediaWiki\CheckUser as CU;
use MediaWiki\Config\HashConfig;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\Request\FauxRequest;
use MediaWiki\User\CentralId\LocalIdLookup;
use MediaWiki\User\User;
use MediaWiki\User\UserFactory;
use Wikimedia\TestingAccessWrapper;

// phpcs:disable MediaWiki.WhiteSpace.SpaceBeforeSingleLineComment.NewLineComment

/**
 * @covers \LoginNotify\LoginNotify
 * @group LoginNotify
 * @group Database
 */
class LoginNotifyTest extends MediaWikiIntegrationTestCase {

	/** @var LoginNotify|TestingAccessWrapper */
	private $inst;

	/** @var UserFactory */
	private $userFactory;

	public function setUpLoginNotify( $configValues = [] ) {
		$day = 86400;
		$config = new HashConfig( $configValues + [
			"LoginNotifyAttemptsKnownIP" => 15,
			"LoginNotifyExpiryKnownIP" => 7 * $day,
			"LoginNotifyAttemptsNewIP" => 5,
			"LoginNotifyExpiryNewIP" => 14 * $day,
			"LoginNotifyCheckKnownIPs" => true,
			"LoginNotifyEnableOnSuccess" => true,
			"LoginNotifySecretKey" => "Secret Stuff!",
			"SecretKey" => "",
			"LoginNotifyCookieExpire" => 180 * $day,
			"LoginNotifyCookieDomain" => null,
			"LoginNotifyMaxCookieRecords" => 6,
			"LoginNotifyCacheLoginIPExpiry" => 60 * $day,
			"LoginNotifyUseCheckUser" => false,
			"LoginNotifyUseSeenTable" => true,
			"LoginNotifyUseCentralId" => false,
			"LoginNotifySeenExpiry" => 180 * $day,
			"LoginNotifySeenBucketSize" => 15 * $day,
			"UpdateRowsPerQuery" => 100,
		] );
		$services = $this->getServiceContainer();
		$this->inst = TestingAccessWrapper::newFromObject(
			new LoginNotify(
				new ServiceOptions( LoginNotify::CONSTRUCTOR_OPTIONS, $config ),
				new HashBagOStuff,
				LoggerFactory::getInstance( 'LoginNotify' ),
				$services->getStatsdDataFactory(),
				$services->getDBLoadBalancerFactory(),
				$services->getJobQueueGroup(),
				new LocalIdLookup(
					new HashConfig( [
						'SharedDB' => false,
						'SharedTables' => [],
						'LocalDatabases' => []
					] ),
					$services->getDBLoadBalancerFactory(),
					$services->getHideUserUtils()
				),
				$services->getAuthManager()
			)
		);
		$this->inst->setLogger( LoggerFactory::getInstance( 'LoginNotify' ) );
		$this->userFactory = $this->getServiceContainer()->getUserFactory();
	}

	/**
	 * @dataProvider provideGetIPNetwork
	 */
	public function testGetIPNetwork( $ip, $expected ) {
		$this->setUpLoginNotify();
		$actual = $this->inst->getIPNetwork( $ip );
		$this->assertSame( $expected, $actual );
	}

	public static function provideGetIPNetwork() {
		return [
			[ '127.0.0.1', '127.0.0.' ],
			[ '118.221.191.18', '118.221.191.' ],
			[ '::1', '0:0:0:0:' ],
			[ '1:2:3:4:5:6:7:8', '1:2:3:4:' ],
			[ '1:ffe1::7:8', '1:FFE1:0:0:' ],
			[ 'd3::1:2:3:4:5:6', 'D3:0:1:2:' ]
		];
	}

	public function testGetIPNetworkInvalid() {
		$this->setUpLoginNotify();
		$this->expectException( UnexpectedValueException::class );
		$this->inst->getIPNetwork( 'localhost' );
	}

	/**
	 * @dataProvider provideGenerateUserCookieRecord
	 */
	public function testGenerateUserCookieRecord( $username, $year, $salt, $expected ) {
		$this->setUpLoginNotify();
		$actual = $this->inst->generateUserCookieRecord( $username, $year, $salt );
		$this->assertEquals( $expected, $actual );
	}

	public static function provideGenerateUserCookieRecord() {
		return [
			[ 'Foo', 2011, 'a4321f', '2011-a4321f-8oerxg4l59zpiu0by7m2to1b4cjeer4' ],
			[ 'Foo', 2011, 'A4321f', '2011-A4321f-in65gc2i9czojfopkeieijc0ek8j5vu' ],
			[ 'Foo', 2015, 'a4321f', '2015-a4321f-2hf2zh9h3afv79b1u4l474ozc0by2xe' ],
			[ 'FOo', 2011, 'a4321f', '2011-a4321f-d0dhdzxg3te3yd3np6xdfrwdrckop7m' ],
		];
	}

	/**
	 * @dataProvider provideIsUserRecordGivenCookie
	 */
	public function testIsUserRecordGivenCookie( $cookieOptions, $expected, $desc ) {
		$this->setUpLoginNotify();
		$user = $this->userFactory->newFromName( 'Foo', UserFactory::RIGOR_NONE );
		if ( is_string( $cookieOptions ) ) {
			$cookieRecord = $cookieOptions;
		} else {
			[ $userName, $year, $salt ] = $cookieOptions + [ false, false, false ];
			$cookieRecord = $this->inst->generateUserCookieRecord( $userName, $year, $salt );
		}
		$actual = $this->inst->isUserRecordGivenCookie( $user, $cookieRecord );
		$this->assertEquals( $expected, $actual, "For {$user->getName()} on test $desc." );
	}

	public static function provideIsUserRecordGivenCookie() {
		$cookie1 = [ 'Foo2' ];
		$cookie2 = [ 'Foo' ];
		$cookie3 = [ 'Foo', gmdate( 'Y' ) - 2 ];
		$cookie4 = [ 'Foo', gmdate( 'Y' ), 'RAND' ];
		$cookie5 = [ 'Foo', gmdate( 'Y' ) - 4 ];
		return [
			[ '2015-in65gc2i9czojfopkeieijc0ek8j5vu', false, "no salt" ],
			[ '2011-A4321f-in65gc2i9czojfopkeieijc0ek8j5vu', false, "too old" ],
			[ $cookie1, false, "name mismatch" ],
			[ $cookie2, true, "normal" ],
			[ $cookie3, true, "2 year old" ],
			[ $cookie4, true, "Specific salt" ],
			[ $cookie5, false, "4 year old" ],
		];
	}

	public function testGetPrevLoginCookie() {
		$this->setUpLoginNotify();
		$req = new FauxRequest();
		$res1 = $this->inst->getPrevLoginCookie( $req );
		$this->assertSame( '', $res1, "no cookie set" );

		$req->setCookie( 'loginnotify_prevlogins', 'foo', '' );
		$res2 = $this->inst->getPrevLoginCookie( $req );
		$this->assertEquals( 'foo', $res2, "get dummy cookie" );
	}

	public function testGetKey() {
		$this->setUpLoginNotify();
		$user1 = $this->userFactory->newFromName( 'Foo_bar' );
		// Make sure proper normalization happens.
		$user2 = $this->userFactory->newFromName( 'Foo__bar' );
		$user3 = $this->userFactory->newFromName( 'Somebody' );

		$this->assertEquals(
			'global:loginnotify:new:ok2qitd5efi25tzjy2l3el4n57g6l3l',
			$this->inst->getKey( $user1, 'new' )
		);
		$this->assertEquals(
			'global:loginnotify:known:ok2qitd5efi25tzjy2l3el4n57g6l3l',
			$this->inst->getKey( $user1, 'known' )
		);
		$this->assertEquals(
			'global:loginnotify:new:ok2qitd5efi25tzjy2l3el4n57g6l3l',
			$this->inst->getKey( $user2, 'new' )
		);
		$this->assertEquals(
			'global:loginnotify:new:tuwpi7e2h9pidovmaxxswk6aq327ewg',
			$this->inst->getKey( $user3, 'new' )
		);
	}

	public function testCheckAndIncKey() {
		$this->setUpLoginNotify();
		$key = 'global:loginnotify:new:tuwpi7e2h9pidovmaxxswk6aq327ewg';
		for ( $i = 1; $i < 5; $i++ ) {
			$res = $this->inst->checkAndIncKey( $key, 5, 3600 );
			$this->assertFalse( $res, "key check numb $i" );
		}
		$this->assertEquals( 5, $this->inst->checkAndIncKey( $key, 5, 3600 ) );
		for ( $i = 1; $i < 5; $i++ ) {
			$res = $this->inst->checkAndIncKey( $key, 5, 3600 );
			$this->assertFalse( $res, "key check numb $i+5" );
		}
		$this->assertEquals( 10, $this->inst->checkAndIncKey( $key, 5, 3600 ) );

		$key2 = 'global:loginnotify:known:tuwpi7e2h9pidovmaxxswk6aq327ewg';
		for ( $i = 1; $i < 5; $i++ ) {
			$res = $this->inst->checkAndIncKey( $key2, 1, 3600 );
			$this->assertEquals( $i, $res, "key check interval 1 numb $i" );
		}
	}

	/**
	 * @dataProvider provideClearCounters
	 */
	public function testClearCounters( $key ) {
		$this->setUpLoginNotify();
		$user = $this->userFactory->newFromName( "Fred" );
		$key = $this->inst->getKey( $user, $key );

		$this->inst->checkAndIncKey( $key, 1, 3600 );
		$res = $this->inst->checkAndIncKey( $key, 1, 3600 );
		$this->assertEquals( 2, $res, "prior to clear" );
		$this->inst->clearCounters( $user );
		$res = $this->inst->checkAndIncKey( $key, 1, 3600 );
		$this->assertSame( 1, $res, "after clear" );
	}

	public static function provideClearCounters() {
		return [
			[ 'new' ],
			[ 'known' ],
		];
	}

	/**
	 * @note Expected new cookie does not include first record, as
	 * first record depends on random numbers.
	 * @dataProvider provideCheckAndGenerateCookie
	 */
	public function testCheckAndGenerateCookie(
		$cookie,
		$expectedSeenBefore,
		$expectedNewCookie,
		$desc
	) {
		$this->setUpLoginNotify();
		$user = $this->userFactory->newFromName( 'Foo' );
		[ $actualSeenBefore, $actualNewCookie ] =
			$this->inst->checkAndGenerateCookie( $user, $cookie );

		$this->assertEquals( $expectedSeenBefore, $actualSeenBefore,
			"[Seen before] $desc"
		);
		$newCookieParts = explode( '.', $actualNewCookie, 2 );
		if ( !isset( $newCookieParts[1] ) ) {
			$newCookieParts[1] = '';
		}
		$this->assertTrue(
			$this->inst->isUserRecordGivenCookie( $user, $newCookieParts[0] ),
			"[Cookie new entry] $desc"
		);
		$this->assertEquals( $expectedNewCookie, $newCookieParts[1], "[Cookie] $desc" );
	}

	public static function provideCheckAndGenerateCookie() {
		$y = gmdate( 'Y' );
		$oldYear = $y - 4;

		$cookie1 = '2023-1skf7bc-5j58f37f3t9m7rhwrrpvi4jigsyn750';
		$cookie2 = '2023-99isim-ab5ms8f8581tpocpumgb78tof6f96yj';
		$cookieOld = '2001-1skf7bc-48j27cdz9fmkv2yf667axiwvdy9z673';
		$cookieOtherUser = "$y-1veusdo-tr049njztrrvkkz4tk3kre8rm1zb134";
		return [
			[ '', false, '', "no cookie" ],
			[
				$cookieOtherUser,
				false,
				$cookieOtherUser,
				"not in cookie"
			],
			[
				"$cookieOtherUser.$y-.$y-abcdefg-8oerxg4l59zpiu0by7m2to1b4cjeer4.$oldYear-" .
					"1234567-tpnsk00419wba6vjh1upif21qtst1cv",
				false,
				"$cookieOtherUser.$y-abcdefg-8oerxg4l59zpiu0by7m2to1b4cjeer4",
				"old values in cookie"
			],
			[
				$cookieOld,
				false,
				"",
				"Only old value"
			],
			[
				$cookie1,
				true,
				"",
				"Normal success"
			],
			[
				"$cookieOtherUser.$cookie1.$cookie2."
					. "$y-1234567-tpnsk00419wba6vjh1upif21qtst1cv.$cookie1",
				true,
				"$cookieOtherUser.$y-1234567-tpnsk00419wba6vjh1upif21qtst1cv",
				"Remove all of current user."
			],
			[
				"$cookieOtherUser.$cookieOtherUser.$cookieOtherUser."
					. "$cookieOtherUser.$cookieOtherUser.$cookieOtherUser."
					. "$cookieOtherUser.$cookieOtherUser.$cookieOtherUser."
					. "$cookieOtherUser.$cookieOtherUser.$cookieOtherUser."
					. "$cookieOtherUser.$cookieOtherUser.$cookieOtherUser."
					. "$cookieOtherUser.$cookieOtherUser.$cookieOtherUser."
					. "$cookieOtherUser.$cookieOtherUser.$cookieOtherUser."
					. "$cookieOtherUser.$cookieOtherUser.$cookieOtherUser."
					. "$cookieOtherUser.$cookieOtherUser.$cookieOtherUser."
					. "$cookieOtherUser.$cookieOtherUser.$cookieOtherUser."
					. "$cookieOtherUser.$cookieOtherUser.$cookieOtherUser",
				false,
				"$cookieOtherUser.$cookieOtherUser.$cookieOtherUser."
					. "$cookieOtherUser.$cookieOtherUser.$cookieOtherUser",
				"Limit max number of records."
			]
		];
	}

	/**
	 * @dataProvider provideValidateCookieRecord
	 */
	public function testValidateCookieRecord( $cookie, $expected ) {
		$this->setUpLoginNotify();
		$this->assertEquals( $expected, $this->inst->validateCookieRecord( $cookie ) );
	}

	public static function provideValidateCookieRecord() {
		$y = gmdate( 'Y' );
		return [
			[ 'fdakslnknfaknasf', false ],
			[ '--', false ],
			[ '91-ffff-fdaskfjlasflasd', false ],
			[ '2011-ffff-fdaskfjlasflasd', false ],
			[ "$y-1veusdo-tr049njztrrvkkz4tk3kre8rm1zb134", true ],
			[ "1991-1veusdo-tr049njztrrvkkz4tk3kre8rm1zb134", false ],
		];
	}

	public function testUserIsInCache() {
		$this->setUpLoginNotify( [ "LoginNotifyUseCheckUser" => true ] );
		$u = $this->userFactory->newFromName( 'Xyzzy' );
		$this->assertSame(
			LoginNotify::USER_NO_INFO,
			$this->inst->userIsInCache( $u, new FauxRequest() )
		);

		$this->inst->cacheLoginIP( $u );
		$this->assertSame(
			LoginNotify::USER_KNOWN,
			$this->inst->userIsInCache( $u, new FauxRequest() )
		);

		$request = new FauxRequest();
		$request->setIP( '10.1.2.3' );

		$this->assertSame(
			LoginNotify::USER_NOT_KNOWN,
			$this->inst->userIsInCache( $u, $request )
		);
	}

	public static function provideRecordFailureKnownCacheOrTable() {
		return [
			[ 'cache' ],
			[ 'table' ]
		];
	}

	/**
	 * @dataProvider provideRecordFailureKnownCacheOrTable
	 * @param string $type
	 */
	public function testRecordFailureKnownCacheOrTable( $type ) {
		$config = [
			'LoginNotifyUseCheckUser' => $type === 'cache',
			'LoginNotifyUseSeenTable' => $type !== 'cache'
		];
		$this->setupRecordFailure( $config );
		$user = $this->getTestUser()->getUser();
		$this->inst->recordKnown( $user );

		// Record a failure, does not notify because the interval is 2
		$this->inst->recordFailure( $user );
		$this->assertNotificationCount( $user, 'login-fail-known', 0 );

		// Record a second failure
		$this->inst->recordFailure( $user );
		$this->assertNotificationCount( $user, 'login-fail-known', 1 );
		$this->assertNotificationCount( $user, 'login-fail-new', 0 );

		// None of our jobs are expected
		$this->runJobs( [ 'numJobs' => 0 ], [ 'type' => 'LoginNotifyChecks' ] );
	}

	public function testRecordFailureKnownCheckUser() {
		$this->setupRecordFailureWithCheckUser();
		$helper = new TestRecentChangesHelper;
		$user = $this->getTestUser()->getUser();

		// Make a fake edit in CheckUser
		$rc = $helper->makeEditRecentChange( $user, 'LoginNotifyTest',
			1, 1, 1, wfTimestampNow(), 0, 0 );
		CU\Hooks::updateCheckUserData( $rc );

		// Record failed login attempt from the same IP
		$this->inst->recordFailure( $user );
		$this->runJobs();
		$this->assertNotificationCount( $user, 'login-fail-known', 0 );

		// Second failure will send a notification
		$this->inst->recordFailure( $user );
		$this->runJobs();
		$this->assertNotificationCount( $user, 'login-fail-known', 1 );
		$this->assertNotificationCount( $user, 'login-fail-new', 0 );
	}

	public function testRecordFailureUnknownCheckUser() {
		$this->setupRecordFailureWithCheckUser();
		$helper = new TestRecentChangesHelper;
		$user = $this->getTestUser()->getUser();

		// Make a fake edit in CheckUser
		$rc = $helper->makeEditRecentChange( $user, 'LoginNotifyTest',
			1, 1, 1, wfTimestampNow(), 0, 0 );
		CU\Hooks::updateCheckUserData( $rc );

		// Change the IP and record a failure
		RequestContext::getMain()->getRequest()->setIP( '127.1.0.0' );
		$this->inst->recordFailure( $user );
		$this->runJobs();
		$this->assertNotificationCount( $user, 'login-fail-new', 0 );

		// Record another failure and notify the user
		$this->inst->recordFailure( $user );
		$this->runJobs();
		$this->assertNotificationCount( $user, 'login-fail-new', 1 );
		$this->assertNotificationCount( $user, 'login-fail-known', 0 );
	}

	public function testRecordFailureSeenExpired() {
		$this->setupRecordFailure( [
			'LoginNotifyAttemptsKnownIP' => 1,
			'LoginNotifyAttemptsNewIP' => 1,
		] );
		$user = $this->getTestUser()->getUser();
		$day = 86400;

		// Mark the IP as known
		$this->inst->setFakeTime( 2 * $day );
		$this->inst->recordKnown( $user );

		// 30 days later, still known
		$this->inst->setFakeTime( 32 * $day );
		$this->inst->recordFailure( $user );
		$this->assertNotificationCount( $user, 'login-fail-new', 0 );
		$this->assertNotificationCount( $user, 'login-fail-known', 1 );

		// 180 days later, rounded up to the nearest bucket, data is expired
		$this->inst->setFakeTime( 210 * $day );
		$this->inst->recordFailure( $user );
		$this->assertNotificationCount( $user, 'login-fail-new', 1 );
		$this->assertNotificationCount( $user, 'login-fail-known', 1 );
	}

	public function testRecordFailureNoPassword() {
		// Don't notify temp users (T329774)
		$this->setupRecordFailure( [
			'LoginNotifyAttemptsKnownIP' => 1,
			'LoginNotifyAttemptsNewIP' => 1,
		] );
		$user = $this->getTestUser()->getUser();
		$this->getDb()->newUpdateQueryBuilder()
			->update( 'user' )
			->set( [ 'user_password' => '' ] )
			->where( [ 'user_id' => $user->getId() ] )
			->caller( __METHOD__ )
			->execute();
		$this->inst->recordFailure( $user );
		$this->assertNotificationCount( $user, 'login-fail-known', 0 );
		$this->assertNotificationCount( $user, 'login-fail-new', 0 );
	}

	public function testSendSuccessNoticeCheckUser() {
		$this->setupRecordFailureWithCheckUser();
		$helper = new TestRecentChangesHelper;
		$user = $this->getTestUser()->getUser();
		$user->setEmail( 'test@test.mediawiki.org' );
		$user->confirmEmail();
		$user->saveSettings();

		$emailSent = false;
		$this->setTemporaryHook( 'EchoAbortEmailNotification',
			static function () use ( &$emailSent ) {
				$emailSent = true;
				return false;
			}
		);

		// Make a fake edit in CheckUser
		$rc = $helper->makeEditRecentChange( $user, 'LoginNotifyTest',
			1, 1, 1, wfTimestampNow(), 0, 0 );
		CU\Hooks::updateCheckUserData( $rc );

		// Change the IP and record a success
		RequestContext::getMain()->getRequest()->setIP( '127.1.0.0' );
		$this->inst->sendSuccessNotice( $user );
		$this->runJobs();
		$this->assertTrue( $emailSent );
	}

	public function testSendSuccessNoticeSeen() {
		$this->setupRecordFailure();
		$user = $this->getTestUser()->getUser();
		$user->setEmail( 'test@test.mediawiki.org' );
		$user->confirmEmail();
		$user->saveSettings();

		$emailSent = false;
		$this->setTemporaryHook( 'EchoAbortEmailNotification',
			static function () use ( &$emailSent ) {
				$emailSent = true;
				return false;
			}
		);

		// Record 127.0.0.1
		$this->inst->recordKnown( $user );

		// Change the IP and record a success
		RequestContext::getMain()->getRequest()->setIP( '127.1.0.0' );
		$this->inst->sendSuccessNotice( $user );
		$this->assertTrue( $emailSent );
	}

	private function setupRecordFailure( $config = [] ) {
		$config += [
			'LoginNotifyAttemptsKnownIP' => 2,
			'LoginNotifyAttemptsNewIP' => 2,
		];
		$this->setUpLoginNotify( $config );
		$this->overrideConfigValues( $config ); // for jobs
	}

	private function setupRecordFailureWithCheckUser() {
		$this->markTestSkippedIfExtensionNotLoaded( 'CheckUser' );
		$this->setupRecordFailure( [ 'LoginNotifyUseCheckUser' => true ] );
	}

	/**
	 * Check that the user has been notified the expected number of times
	 *
	 * @param User $user
	 * @param string $type
	 * @param int $expected
	 */
	private function assertNotificationCount( $user, $type, $expected ) {
		$this->newSelectQueryBuilder()
			->select( 'COUNT(*)' )
			->from( 'echo_notification' )
			->join( 'echo_event', null, 'event_id=notification_event' )
			->where( [
				'notification_user' => $user->getId(),
				'event_type' => $type
			] )
			->assertFieldValue( $expected );
	}

	public static function providePackedSignedInt64ToDecimal() {
		return [
			[ '0000000000000000', 0 ],
			[ '0000000000000001', 1 ],
			[ 'ffffffffffffffff', -1 ],
			[ '7fffffffffffffff', '9223372036854775807' ],
			[ '8000000000000000', '-9223372036854775808' ],
		];
	}

	/**
	 * @dataProvider providePackedSignedInt64ToDecimal
	 * @param string $hexInput
	 * @param string|int $expected
	 */
	public function testPackedSignedInt64ToDecimal( $hexInput, $expected ) {
		$class = TestingAccessWrapper::newFromClass( LoginNotify::class );
		$input = hex2bin( $hexInput );
		$result = $class->packedSignedInt64ToDecimal( $input );
		$this->assertSame( (string)$expected, (string)$result );
	}

	public function testPurgeSeen() {
		$this->setupLoginNotify( [ 'UpdateRowsPerQuery' => 1 ] );
		$user = $this->getTestUser()->getUser();
		$day = 86400;
		$this->inst->setFakeTime( 0 );
		$this->inst->recordKnown( $user );
		$this->inst->setFakeTime( 90 * $day );
		$this->inst->recordKnown( $user );
		$this->inst->setFakeTime( 210 * $day );
		$this->inst->recordKnown( $user );

		$this->assertSeenCount( 3 );

		$this->inst->setFakeTime( 300 * $day );
		$minId = $this->inst->getMinExpiredId();
		$this->assertGreaterThan( 0, $minId );

		$nextId = $this->inst->purgeSeen( $minId );
		$this->assertSeenCount( 2 );
		$this->assertGreaterThan( $minId, $nextId );

		$nextId = $this->inst->purgeSeen( $nextId );
		$this->assertGreaterThan( $minId, $nextId );
		$this->assertSeenCount( 1 );

		$nextId = $this->inst->purgeSeen( $nextId );
		$this->assertNull( $nextId );
	}

	public function testPurgeViaJob() {
		$this->setupLoginNotify( [ 'UpdateRowsPerQuery' => 1 ] );
		$user = $this->getTestUser()->getUser();

		$this->inst->setFakeTime( 0 ); // 1970
		$this->inst->recordUserInSeenTable( $user );
		$this->assertSeenCount( 1 );

		$this->inst->setFakeTime( null ); // real current time
		$this->inst->recordUserInSeenTable( $user );
		$this->assertSeenCount( 2 );

		$this->runJobs();
		$this->assertSeenCount( 1 );
	}

	private function assertSeenCount( $expected ) {
		$this->newSelectQueryBuilder()
			->select( 'COUNT(*)' )
			->from( 'loginnotify_seen_net' )
			->assertFieldValue( $expected );
	}
}

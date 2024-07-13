<?php

use MediaWiki\Extension\Notifications\EmailFormat;
use MediaWiki\Extension\Notifications\Gateway\UserNotificationGateway;
use MediaWiki\Extension\Notifications\Mapper\NotificationMapper;
use MediaWiki\Extension\Notifications\Mapper\TargetPageMapper;
use MediaWiki\Extension\Notifications\Model\Event;
use MediaWiki\Extension\Notifications\Model\Notification;
use MediaWiki\Extension\Notifications\NotifUser;
use MediaWiki\User\Options\UserOptionsLookup;
use MediaWiki\User\TalkPageNotificationManager;
use MediaWiki\User\User;
use MediaWiki\User\UserFactory;
use MediaWiki\User\UserGroupManager;
use Wikimedia\Rdbms\IDatabase;

/**
 * @covers \MediaWiki\Extension\Notifications\NotifUser
 * @group Echo
 */
class NotifUserTest extends MediaWikiIntegrationTestCase {

	/**
	 * @var WANObjectCache
	 */
	private $cache;

	protected function setUp(): void {
		parent::setUp();
		$this->cache = new WANObjectCache( [
			'cache' => $this->getServiceContainer()->getMainObjectStash(),
		] );
	}

	public function testNewFromUser() {
		$exception = false;
		try {
			NotifUser::newFromUser( User::newFromId( 0 ) );
		} catch ( Exception $e ) {
			$exception = true;
			$this->assertEquals( "User must be logged in to view notification!",
				$e->getMessage() );
		}
		$this->assertTrue( $exception, "Got exception" );

		$notifUser = NotifUser::newFromUser( User::newFromId( 2 ) );
		$this->assertInstanceOf( NotifUser::class, $notifUser );
	}

	public function testGetEmailFormat() {
		$user = User::newFromId( 2 );
		$pref = 'foo';
		$userOptionsLookup = $this->createMock( UserOptionsLookup::class );
		$userOptionsLookup->expects( $this->atLeastOnce() )
			->method( 'getOption' )
			->with( $user, 'echo-email-format' )
			->willReturn( $pref );
		$this->setService( 'UserOptionsLookup', $userOptionsLookup );
		$notifUser = NotifUser::newFromUser( $user );

		$this->setMwGlobals( 'wgAllowHTMLEmail', true );
		$this->assertEquals( $notifUser->getEmailFormat(), $pref );
		$this->setMwGlobals( 'wgAllowHTMLEmail', false );
		$this->assertEquals( EmailFormat::PLAIN_TEXT, $notifUser->getEmailFormat() );
	}

	public function testMarkRead() {
		$this->setService( 'UserFactory', $this->createMock( UserFactory::class ) );
		$this->setService( 'TalkPageNotificationManager', $this->createMock( TalkPageNotificationManager::class ) );
		$notifUser = new NotifUser(
			User::newFromId( 2 ),
			$this->cache,
			$this->mockUserNotificationGateway( [ 'markRead' => true ] ),
			$this->mockNotificationMapper(),
			$this->createMock( TargetPageMapper::class ),
			$this->createNoOpMock( UserOptionsLookup::class ),
			$this->getServiceContainer()->getUserFactory(),
			$this->getServiceContainer()->getReadOnlyMode()
		);
		$this->assertFalse( $notifUser->markRead( [] ) );
		$this->assertTrue( $notifUser->markRead( [ 1 ] ) );

		$notifUser = new NotifUser(
			User::newFromId( 2 ),
			$this->cache,
			$this->mockUserNotificationGateway( [ 'markRead' => false ] ),
			$this->mockNotificationMapper(),
			$this->createMock( TargetPageMapper::class ),
			$this->createNoOpMock( UserOptionsLookup::class ),
			$this->getServiceContainer()->getUserFactory(),
			$this->getServiceContainer()->getReadOnlyMode()
		);
		$this->assertFalse( $notifUser->markRead( [] ) );
		$this->assertFalse( $notifUser->markRead( [ 1 ] ) );
	}

	public function testMarkAllRead() {
		$this->setService( 'UserFactory', $this->createMock( UserFactory::class ) );
		$this->setService( 'TalkPageNotificationManager', $this->createMock( TalkPageNotificationManager::class ) );
		$this->setService( 'UserGroupManager', $this->createMock( UserGroupManager::class ) );
		$this->setService( 'UserOptionsLookup', $this->createMock( UserOptionsLookup::class ) );
		// Successful mark as read & non empty fetch
		$notifUser = new NotifUser(
			User::newFromId( 2 ),
			$this->cache,
			$this->mockUserNotificationGateway( [ 'markRead' => true ] ),
			$this->mockNotificationMapper( [ $this->mockNotification() ] ),
			$this->createMock( TargetPageMapper::class ),
			$this->createNoOpMock( UserOptionsLookup::class ),
			$this->getServiceContainer()->getUserFactory(),
			$this->getServiceContainer()->getReadOnlyMode()
		);
		$this->assertTrue( $notifUser->markAllRead() );

		// Unsuccessful mark as read & non empty fetch
		$notifUser = new NotifUser(
			User::newFromId( 2 ),
			$this->cache,
			$this->mockUserNotificationGateway( [ 'markRead' => false ] ),
			$this->mockNotificationMapper( [ $this->mockNotification() ] ),
			$this->createMock( TargetPageMapper::class ),
			$this->createNoOpMock( UserOptionsLookup::class ),
			$this->getServiceContainer()->getUserFactory(),
			$this->getServiceContainer()->getReadOnlyMode()
		);
		$this->assertFalse( $notifUser->markAllRead() );

		// Successful mark as read & empty fetch
		$notifUser = new NotifUser(
			User::newFromId( 2 ),
			$this->cache,
			$this->mockUserNotificationGateway( [ 'markRead' => true ] ),
			$this->mockNotificationMapper(),
			$this->createMock( TargetPageMapper::class ),
			$this->createNoOpMock( UserOptionsLookup::class ),
			$this->getServiceContainer()->getUserFactory(),
			$this->getServiceContainer()->getReadOnlyMode()
		);
		$this->assertFalse( $notifUser->markAllRead() );

		// Unsuccessful mark as read & empty fetch
		$notifUser = new NotifUser(
			User::newFromId( 2 ),
			$this->cache,
			$this->mockUserNotificationGateway( [ 'markRead' => false ] ),
			$this->mockNotificationMapper(),
			$this->createMock( TargetPageMapper::class ),
			$this->createNoOpMock( UserOptionsLookup::class ),
			$this->getServiceContainer()->getUserFactory(),
			$this->getServiceContainer()->getReadOnlyMode()
		);
		$this->assertFalse( $notifUser->markAllRead() );
	}

	public function mockUserNotificationGateway( array $dbResult = [] ) {
		$dbResult += [
			'markRead' => true
		];
		$gateway = $this->createMock( UserNotificationGateway::class );
		$gateway->method( 'markRead' )
			->willReturn( $dbResult['markRead'] );
		$gateway->method( 'getDB' )
			->willReturn( $this->createMock( IDatabase::class ) );

		return $gateway;
	}

	public function mockNotificationMapper( array $result = [] ) {
		$mapper = $this->createMock( NotificationMapper::class );
		$mapper->method( 'fetchUnreadByUser' )
			->willReturn( $result );

		return $mapper;
	}

	protected function mockNotification() {
		$notification = $this->createMock( Notification::class );
		$notification->method( 'getEvent' )
			->willReturn( $this->mockEvent() );

		return $notification;
	}

	protected function mockEvent() {
		$event = $this->createMock( Event::class );
		$event->method( 'getId' )
			->willReturn( 1 );

		return $event;
	}

	protected function newNotifUser() {
		return new NotifUser(
			User::newFromId( 2 ),
			$this->cache,
			$this->mockUserNotificationGateway(),
			$this->mockNotificationMapper(),
			$this->createMock( TargetPageMapper::class ),
			$this->createNoOpMock( UserOptionsLookup::class ),
			$this->getServiceContainer()->getUserFactory(),
			$this->getServiceContainer()->getReadOnlyMode()
		);
	}
}

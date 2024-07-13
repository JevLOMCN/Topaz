<?php

use MediaWiki\Extension\Notifications\DbFactory;
use MediaWiki\Extension\Notifications\Mapper\NotificationMapper;
use MediaWiki\Extension\Notifications\Model\Notification;
use MediaWiki\User\User;
use Wikimedia\Rdbms\IDatabase;

/**
 * @covers \MediaWiki\Extension\Notifications\Mapper\NotificationMapper
 */
class NotificationMapperTest extends MediaWikiIntegrationTestCase {

	/**
	 * @todo write this test
	 */
	public function testInsert() {
		$this->assertTrue( true );
	}

	public function fetchUnreadByUser( User $user, $limit, array $eventTypes = [] ) {
		// Unsuccessful select
		$notifMapper = new NotificationMapper( $this->mockDbFactory( [ 'select' => false ] ) );
		$res = $notifMapper->fetchUnreadByUser( $this->mockUser(), 10, null, '' );
		$this->assertSame( [], $res );

		// Successful select
		$dbResult = [
			(object)[
				'event_id' => 1,
				'event_type' => 'test_event',
				'event_variant' => '',
				'event_extra' => '',
				'event_page_id' => '',
				'event_agent_id' => '',
				'event_agent_ip' => '',
				'notification_user' => 1,
				'notification_timestamp' => '20140615101010',
				'notification_read_timestamp' => null,
				'notification_bundle_hash' => 'testhash',
			]
		];
		$notifMapper = new NotificationMapper( $this->mockDbFactory( [ 'select' => $dbResult ] ) );
		$res = $notifMapper->fetchUnreadByUser( $this->mockUser(), 10, null, '', [] );
		$this->assertSame( [], $res );

		$notifMapper = new NotificationMapper( $this->mockDbFactory( [ 'select' => $dbResult ] ) );
		$res = $notifMapper->fetchUnreadByUser( $this->mockUser(), 10, null, '', [ 'test_event' ] );
		$this->assertIsArray( $res );
		$this->assertNotEmpty( $res );
		foreach ( $res as $row ) {
			$this->assertInstanceOf( Notification::class, $row );
		}
	}

	public function testFetchByUser() {
		// Unsuccessful select
		$notifMapper = new NotificationMapper( $this->mockDbFactory( [ 'select' => false ] ) );
		$res = $notifMapper->fetchByUser( $this->mockUser(), 10, '' );
		$this->assertSame( [], $res );

		// Successful select
		$notifDbResult = [
			(object)[
				'event_id' => 1,
				'event_type' => 'test_event',
				'event_variant' => '',
				'event_extra' => '',
				'event_page_id' => '',
				'event_agent_id' => '',
				'event_agent_ip' => '',
				'event_deleted' => 0,
				'notification_user' => 1,
				'notification_timestamp' => '20140615101010',
				'notification_read_timestamp' => '20140616101010',
				'notification_bundle_hash' => 'testhash',
			]
		];

		$notifMapper = new NotificationMapper( $this->mockDbFactory( [ 'select' => $notifDbResult ] ) );
		$res = $notifMapper->fetchByUser( $this->mockUser(), 10, '', [] );
		$this->assertSame( [], $res );

		$notifMapper = new NotificationMapper(
			$this->mockDbFactory( [ 'select' => $notifDbResult ] )
		);
		$res = $notifMapper->fetchByUser( $this->mockUser(), 10, '', [ 'test_event' ] );
		$this->assertIsArray( $res );
		$this->assertNotEmpty( $res );
		foreach ( $res as $row ) {
			$this->assertInstanceOf( Notification::class, $row );
		}

		$notifMapper = new NotificationMapper( $this->mockDbFactory( [] ) );
		$res = $notifMapper->fetchByUser( $this->mockUser(), 10, '' );
		$this->assertSame( [], $res );
	}

	public function testFetchByUserOffset() {
		// Unsuccessful select
		$notifMapper = new NotificationMapper( $this->mockDbFactory( [ 'selectRow' => false ] ) );
		$res = $notifMapper->fetchByUserOffset( User::newFromId( 1 ), 500 );
		$this->assertFalse( $res );

		// Successful select
		$dbResult = (object)[
			'event_id' => 1,
			'event_type' => 'test',
			'event_variant' => '',
			'event_extra' => '',
			'event_page_id' => '',
			'event_agent_id' => '',
			'event_agent_ip' => '',
			'event_deleted' => 0,
			'notification_user' => 1,
			'notification_timestamp' => '20140615101010',
			'notification_read_timestamp' => '20140616101010',
			'notification_bundle_hash' => 'testhash',
		];
		$notifMapper = new NotificationMapper( $this->mockDbFactory( [ 'selectRow' => $dbResult ] ) );
		$row = $notifMapper->fetchByUserOffset( User::newFromId( 1 ), 500 );
		$this->assertInstanceOf( Notification::class, $row );
	}

	public function testDeleteByUserEventOffset() {
		$this->setMwGlobals( [ 'wgUpdateRowsPerQuery' => 4 ] );
		$mockDb = $this->createMock( IDatabase::class );
		$makeResultRows = static function ( $eventIds ) {
			return new ArrayIterator( array_map( static function ( $eventId ) {
				return (object)[ 'notification_event' => $eventId ];
			}, $eventIds ) );
		};
		$mockDb->expects( $this->exactly( 4 ) )
			->method( 'select' )
			->willReturnOnConsecutiveCalls(
				$this->returnValue( $makeResultRows( [ 1, 2, 3, 5 ] ) ),
				$this->returnValue( $makeResultRows( [ 8, 13, 21, 34 ] ) ),
				$this->returnValue( $makeResultRows( [ 55, 89 ] ) ),
				$this->returnValue( $makeResultRows( [] ) )
			);
		$mockDb->expects( $this->exactly( 3 ) )
			->method( 'selectFieldValues' )
			->willReturnOnConsecutiveCalls(
				$this->returnValue( [] ),
				$this->returnValue( [ 13, 21 ] ),
				$this->returnValue( [ 55 ] )
			);
		$expectedArgs = [
			[
				'echo_notification',
				[ 'notification_user' => 1, 'notification_event' => [ 1, 2, 3, 5 ] ],
			],
			[
				'echo_notification',
				[ 'notification_user' => 1, 'notification_event' => [ 8, 13, 21, 34 ] ],
			],
			[
				'echo_event',
				[ 'event_id' => [ 13, 21 ] ],
			],
			[
				'echo_target_page',
				[ 'etp_event' => [ 13, 21 ] ],
			],
			[
				'echo_notification',
				[ 'notification_user' => 1, 'notification_event' => [ 55, 89 ] ],
			],
			[
				'echo_event',
				[ 'event_id' => [ 55 ] ],
			],
			[
				'echo_target_page',
				[ 'etp_event' => [ 55 ] ],
			]
		];
		$mockDb->expects( $this->exactly( count( $expectedArgs ) ) )
			->method( 'delete' )
			->willReturnCallback( function ( $table, $conds ) use ( &$expectedArgs ): bool {
				$this->assertSame( array_shift( $expectedArgs ), [ $table, $conds ] );
				return true;
			} );

		$notifMapper = new NotificationMapper( $this->mockDbFactory( $mockDb ) );
		$this->assertTrue( $notifMapper->deleteByUserEventOffset( User::newFromId( 1 ), 500 ) );
	}

	/**
	 * Mock object of User
	 * @return User
	 */
	protected function mockUser() {
		$user = $this->createMock( User::class );
		$user->method( 'getID' )
			->willReturn( 1 );

		return $user;
	}

	/**
	 * Mock object of Notification
	 * @return Notification
	 */
	protected function mockNotification() {
		$event = $this->createMock( Notification::class );
		$event->method( 'toDbArray' )
			->willReturn( [] );

		return $event;
	}

	/**
	 * Mock object of DbFactory
	 *
	 * @param array|IDatabase $dbResultOrMockDb
	 *
	 * @return DbFactory
	 */
	protected function mockDbFactory( $dbResultOrMockDb ) {
		$mockDb = is_array( $dbResultOrMockDb ) ? $this->mockDb( $dbResultOrMockDb ) : $dbResultOrMockDb;
		$dbFactory = $this->createMock( DbFactory::class );
		$dbFactory->method( 'getEchoDb' )
			->willReturn( $mockDb );

		return $dbFactory;
	}

	/**
	 * Returns a mock database object
	 *
	 * @param array $dbResult
	 *
	 * @return IDatabase
	 */
	protected function mockDb( array $dbResult ) {
		$dbResult += [
			'insert' => '',
			'select' => '',
			'selectRow' => '',
			'delete' => ''
		];

		$db = $this->createMock( IDatabase::class );
		$db->method( 'insert' )
			->willReturn( $dbResult['insert'] );
		$db->method( 'select' )
			->willReturn( $dbResult['select'] );
		$db->method( 'delete' )
			->willReturn( $dbResult['delete'] );
		$db->method( 'selectRow' )
			->willReturn( $dbResult['selectRow'] );
		$db->method( 'onTransactionCommitOrIdle' )
			->will( new EchoExecuteFirstArgumentStub );

		return $db;
	}

}

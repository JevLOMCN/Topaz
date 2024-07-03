<?php

use MediaWiki\Extension\Notifications\DbFactory;
use MediaWiki\Extension\Notifications\UnreadWikis;
use MediaWiki\Utils\MWTimestamp;
use Wikimedia\TestingAccessWrapper;

/**
 * Tests for unread wiki database access
 *
 * @group Database
 * @covers \MediaWiki\Extension\Notifications\UnreadWikis
 */
class UnreadWikisTest extends MediaWikiIntegrationTestCase {

	public function testUpdateCount() {
		$unread = TestingAccessWrapper::newFromObject( new UnreadWikis( 1 ) );
		$unread->dbFactory = $this->mockDbFactory( $this->db );
		$unread->updateCount(
			'foobar',
			2,
			new MWTimestamp( '20220322222222' ),
			3,
			new MWTimestamp( '20220322222223' )
		);
		$this->assertSame(
			[
				'foobar' => [
					'alert' => [ 'count' => '2', 'ts' => '20220322222222' ],
					'message' => [ 'count' => '3', 'ts' => '20220322222223' ],
				]
			],
			$unread->getUnreadCounts()
		);
	}

	public function testUpdateCountFalse() {
		$unread = TestingAccessWrapper::newFromObject( new UnreadWikis( 1 ) );
		$unread->dbFactory = $this->mockDbFactory( $this->db );
		$unread->updateCount(
			'foobar',
			3,
			false,
			4,
			false
		);
		$this->assertSame(
			[
				'foobar' => [
					'alert' => [ 'count' => '3', 'ts' => '00000000000000' ],
					'message' => [ 'count' => '4', 'ts' => '00000000000000' ],
				]
			],
			$unread->getUnreadCounts()
		);
	}

	/**
	 * Mock object of DbFactory
	 * @param \Wikimedia\Rdbms\IDatabase $db
	 * @return DbFactory
	 */
	protected function mockDbFactory( $db ) {
		$dbFactory = $this->createMock( DbFactory::class );
		$dbFactory->expects( $this->any() )
			->method( 'getSharedDb' )
			->willReturn( $db );

		return $dbFactory;
	}
}

<?php

use MediaWiki\Extension\Notifications\DbFactory;
use MediaWiki\Extension\Notifications\Mapper\TargetPageMapper;
use MediaWiki\Extension\Notifications\Model\TargetPage;
use Wikimedia\Rdbms\IDatabase;

/**
 * @covers \MediaWiki\Extension\Notifications\Mapper\TargetPageMapper
 */
class TargetPageMapperTest extends MediaWikiUnitTestCase {

	public static function provideDataTestInsert() {
		return [
			[
				'successful insert with next sequence = 1',
				[ 'insert' => true, 'insertId' => 2 ],
				1
			],
			[
				'successful insert with insert id = 2',
				[ 'insert' => true, 'insertId' => 2 ],
				2
			],
		];
	}

	/**
	 * @dataProvider provideDataTestInsert
	 */
	public function testInsert( $message, $dbResult, $result ) {
		$target = $this->mockTargetPage();
		$targetMapper = new TargetPageMapper( $this->mockDbFactory( $dbResult ) );
		$this->assertEquals( $result, $targetMapper->insert( $target ), $message );
	}

	/**
	 * Mock object of TargetPage
	 * @return TargetPage
	 */
	protected function mockTargetPage() {
		$target = $this->createMock( TargetPage::class );
		$target->method( 'toDbArray' )
			->willReturn( [] );
		$target->method( 'getPageId' )
			->willReturn( 2 );
		$target->method( 'getEventId' )
			->willReturn( 3 );

		return $target;
	}

	/**
	 * Mock object of DbFactory
	 * @param array $dbResult
	 * @return DbFactory
	 */
	protected function mockDbFactory( $dbResult ) {
		$dbFactory = $this->createMock( DbFactory::class );
		$dbFactory->method( 'getEchoDb' )
			->willReturn( $this->mockDb( $dbResult ) );

		return $dbFactory;
	}

	/**
	 * Returns a mock database object
	 * @param array $dbResult
	 * @return IDatabase
	 */
	protected function mockDb( array $dbResult ) {
		$dbResult += [
			'insert' => '',
			'insertId' => '',
			'select' => '',
			'delete' => ''
		];
		$db = $this->createMock( IDatabase::class );
		$db->method( 'insert' )
			->willReturn( $dbResult['insert'] );
		$db->method( 'insertId' )
			->willReturn( $dbResult['insertId'] );
		$db->method( 'select' )
			->willReturn( $dbResult['select'] );
		$db->method( 'delete' )
			->willReturn( $dbResult['delete'] );

		return $db;
	}

}

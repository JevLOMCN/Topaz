<?php

namespace MediaWiki\Extension\AbuseFilter\Tests\Unit;

use MediaWiki\Extension\AbuseFilter\CentralDBManager;
use MediaWiki\Extension\AbuseFilter\CentralDBNotAvailableException;
use MediaWikiUnitTestCase;
use Wikimedia\Rdbms\DBConnRef;
use Wikimedia\Rdbms\ILoadBalancer;
use Wikimedia\Rdbms\LBFactory;

/**
 * @group Test
 * @group AbuseFilter
 * @coversDefaultClass \MediaWiki\Extension\AbuseFilter\CentralDBManager
 */
class CentralDBManagerTest extends MediaWikiUnitTestCase {
	/**
	 * @covers ::__construct
	 */
	public function testConstruct() {
		$this->assertInstanceOf(
			CentralDBManager::class,
			new CentralDBManager(
				$this->createMock( LBFactory::class ),
				'foo',
				true
			)
		);
	}

	/**
	 * @covers ::getConnection
	 */
	public function testGetConnection() {
		$expected = $this->createMock( DBConnRef::class );
		$lb = $this->createMock( ILoadBalancer::class );
		$lb->method( 'getConnection' )->willReturn( $expected );
		$lbFactory = $this->createMock( LBFactory::class );
		$lbFactory->method( 'getMainLB' )->willReturn( $lb );
		$dbManager = new CentralDBManager( $lbFactory, 'foo', true );
		$this->assertSame( $expected, $dbManager->getConnection( DB_REPLICA ) );
	}

	/**
	 * @covers ::getConnection
	 */
	public function testGetConnection_invalid() {
		$lbFactory = $this->createMock( LBFactory::class );
		$dbManager = new CentralDBManager( $lbFactory, null, true );
		$this->expectException( CentralDBNotAvailableException::class );
		$dbManager->getConnection( DB_REPLICA );
	}

	/**
	 * @covers ::getCentralDBName
	 */
	public function testGetCentralDBName() {
		$expected = 'foobar';
		$lbFactory = $this->createMock( LBFactory::class );
		$dbManager = new CentralDBManager( $lbFactory, $expected, true );
		$this->assertSame( $expected, $dbManager->getCentralDBName() );
	}

	/**
	 * @covers ::getCentralDBName
	 */
	public function testGetCentralDBName_invalid() {
		$lbFactory = $this->createMock( LBFactory::class );
		$dbManager = new CentralDBManager( $lbFactory, null, true );
		$this->expectException( CentralDBNotAvailableException::class );
		$dbManager->getCentralDBName();
	}

	/**
	 * @param bool $value
	 * @covers ::filterIsCentral
	 * @dataProvider provideIsCentral
	 */
	public function testFilterIsCentral( bool $value ) {
		$lbFactory = $this->createMock( LBFactory::class );
		$dbManager = new CentralDBManager( $lbFactory, 'foo', $value );
		$this->assertSame( $value, $dbManager->filterIsCentral() );
	}

	public static function provideIsCentral() {
		return [
			'central' => [ true ],
			'not central' => [ false ]
		];
	}
}

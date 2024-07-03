<?php

use MediaWiki\Extension\Notifications\DbFactory;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\ILoadBalancer;

/**
 * @covers \MediaWiki\Extension\Notifications\DbFactory
 * @group Database
 */
class DbFactoryTest extends MediaWikiIntegrationTestCase {

	public function testNewFromDefault() {
		$db = DbFactory::newFromDefault();
		$this->assertInstanceOf( DbFactory::class, $db );

		return $db;
	}

	/**
	 * @depends testNewFromDefault
	 */
	public function testGetEchoDb( DbFactory $db ) {
		$this->assertInstanceOf( IDatabase::class, $db->getEchoDb( DB_PRIMARY ) );
		$this->assertInstanceOf( IDatabase::class, $db->getEchoDb( DB_REPLICA ) );
	}

	/**
	 * @depends testNewFromDefault
	 */
	public function testGetLB( DbFactory $db ) {
		$reflection = new ReflectionClass( DbFactory::class );
		$method = $reflection->getMethod( 'getLB' );
		$method->setAccessible( true );
		$this->assertInstanceOf( ILoadBalancer::class, $method->invoke( $db ) );
	}

}

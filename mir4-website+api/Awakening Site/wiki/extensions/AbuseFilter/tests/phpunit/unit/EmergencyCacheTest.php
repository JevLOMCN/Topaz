<?php

namespace MediaWiki\Extension\AbuseFilter\Tests\Unit;

use HashBagOStuff;
use MediaWiki\Extension\AbuseFilter\EmergencyCache;
use MediaWikiUnitTestCase;

/**
 * @coversDefaultClass \MediaWiki\Extension\AbuseFilter\EmergencyCache
 */
class EmergencyCacheTest extends MediaWikiUnitTestCase {

	/**
	 * @covers ::__construct
	 * @covers ::getFiltersToCheckInGroup
	 * @covers ::getForFilter
	 */
	public function testEmptyCache() {
		$cache = new EmergencyCache( new HashBagOStuff(), [ 'default' => 86400 ] );
		$this->assertSame( [], $cache->getFiltersToCheckInGroup( 'default' ) );
		$this->assertFalse( $cache->getForFilter( 1 ) );
	}

	/**
	 * @covers ::setNewForFilter
	 * @covers ::getFiltersToCheckInGroup
	 * @covers ::getForFilter
	 * @covers ::createGroupKey
	 * @covers ::createFilterKey
	 */
	public function testSetNewForFilter() {
		$time = microtime( true );
		$stash = new HashBagOStuff();
		$stash->setMockTime( $time );
		$cache = new EmergencyCache( $stash, [ 'default' => 86400, 'other' => 3600 ] );
		$cache->setNewForFilter( 2, 'other' );
		$this->assertSame(
			[ 'total' => 0, 'matches' => 0 ],
			$cache->getForFilter( 2 )
		);
		$this->assertSame(
			[ 2 ],
			$cache->getFiltersToCheckInGroup( 'other' )
		);
		$this->assertSame(
			[],
			$cache->getFiltersToCheckInGroup( 'default' )
		);

		$time += 3599;
		$this->assertNotFalse( $cache->getForFilter( 2 ) );
		$time += 2;
		$this->assertFalse( $cache->getForFilter( 2 ) );
		$this->assertSame( [], $cache->getFiltersToCheckInGroup( 'other' ) );
	}

	/**
	 * @covers ::incrementForFilter
	 * @covers ::getForFilter
	 * @covers ::setNewForFilter
	 */
	public function testIncrementForFilter() {
		$time = microtime( true );
		$stash = new HashBagOStuff();
		$stash->setMockTime( $time );
		$cache = new EmergencyCache( $stash, [ 'default' => 86400 ] );
		$cache->setNewForFilter( 1, 'default' );
		$cache->incrementForFilter( 1, false );
		$this->assertSame(
			[ 'total' => 1, 'matches' => 0 ],
			$cache->getForFilter( 1 )
		);
		$cache->incrementForFilter( 1, true );
		$this->assertSame(
			[ 'total' => 2, 'matches' => 1 ],
			$cache->getForFilter( 1 )
		);

		$time += 86401;
		$cache->incrementForFilter( 1, true );
		$this->assertFalse( $cache->getForFilter( 1 ) );
	}

	/**
	 * @covers ::getFiltersToCheckInGroup
	 */
	public function testGetFiltersToCheckInGroup() {
		$time = microtime( true );
		$stash = new HashBagOStuff();
		$stash->setMockTime( $time );
		$cache = new EmergencyCache( $stash, [ 'default' => 3600 ] );
		$cache->setNewForFilter( 1, 'default' );
		$time += 1000;
		$cache->setNewForFilter( 2, 'default' );
		$this->assertArrayEquals(
			[ 1, 2 ],
			$cache->getFiltersToCheckInGroup( 'default' )
		);
		$time += 2601;
		$this->assertArrayEquals(
			[ 2 ],
			$cache->getFiltersToCheckInGroup( 'default' )
		);
	}

}

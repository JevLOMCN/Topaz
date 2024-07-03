<?php

namespace MediaWiki\Extension\Notifications\Test;

use HashBagOStuff;
use MediaWiki\Extension\Notifications\ArrayList;
use MediaWiki\Extension\Notifications\CachedList;
use MediaWiki\Extension\Notifications\ContainmentSet;
use MediaWiki\Extension\Notifications\OnWikiList;
use MediaWikiIntegrationTestCase;
use WANObjectCache;

/**
 * @covers \MediaWiki\Extension\Notifications\ContainmentSet
 * @group Echo
 * @group Database
 */
class ContainmentSetTest extends MediaWikiIntegrationTestCase {

	public function testGenericContains() {
		$list = new ContainmentSet( self::getTestUser()->getUser() );

		$list->addArray( [ 'foo', 'bar' ] );
		$this->assertTrue( $list->contains( 'foo' ) );
		$this->assertTrue( $list->contains( 'bar' ) );
		$this->assertFalse( $list->contains( 'whammo' ) );

		$list->addArray( [ 'whammo' ] );
		$this->assertTrue( $list->contains( 'whammo' ) );

		$list->addArray( [ 0 ] );
		$this->assertFalse( $list->contains( 'baz' ) );
	}

	public function testCachedListInnerListIsOnlyCalledOnce() {
		// simulate caching
		$innerCache = new HashBagOStuff;
		$wanCache = new WANObjectCache( [ 'cache' => $innerCache ] );

		$inner = [ 'bing', 'bang' ];
		// We use a mock instead of the real thing for the $this->once() assertion
		// verifying that the cache doesn't just keep asking the inner object
		$list = $this->createMock( ArrayList::class );
		$list->expects( $this->once() )
			->method( 'getValues' )
			->willReturn( $inner );
		$list->method( 'getCacheKey' )->willReturn( '' );

		$cached = new CachedList( $wanCache, 'test_key', $list );

		// First run through should hit the main list, and save to innerCache
		$this->assertEquals( $inner, $cached->getValues() );
		$this->assertEquals( $inner, $cached->getValues() );

		// Reinitialize to get a fresh instance that will pull directly from
		// innerCache without hitting the $list
		$freshCached = new CachedList( $wanCache, 'test_key', $list );
		$this->assertEquals( $inner, $freshCached->getValues() );
	}

	/**
	 * @group Database
	 */
	public function testOnWikiList() {
		$this->editPage( 'User:Foo/Bar-baz', "abc\ndef\r\nghi\n\n\n" );

		$list = new OnWikiList( NS_USER, "Foo/Bar-baz" );
		$this->assertEquals(
			[ 'abc', 'def', 'ghi' ],
			$list->getValues()
		);
	}

	public function testOnWikiListNonExistant() {
		$list = new OnWikiList( NS_USER, "Some_Non_Existant_Page" );
		$this->assertEquals( [], $list->getValues() );
	}
}

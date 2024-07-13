<?php

use MediaWiki\Extension\Notifications\Cache\TitleLocalCache;
use MediaWiki\Title\Title;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers MediaWiki\Extension\Notifications\Cache\TitleLocalCache
 * @group Database
 */
class TitleLocalCacheTest extends MediaWikiIntegrationTestCase {

	public function testAdd() {
		$cache = $this->mockTitleLocalCache();

		$cache->add( 1 );
		$cache->add( 9 );

		// Resolutions should be batched
		$cache->expects( $this->once() )->method( 'resolve' )
			->with( [ 1, 9 ] )->willReturn( [] );

		// Trigger
		$cache->get( 9 );
	}

	public function testGet() {
		$cache = $this->mockTitleLocalCache();
		$cachePriv = TestingAccessWrapper::newFromObject( $cache );

		// First title included in cache
		$res1 = $this->insertPage( 'TitleLocalCacheTest_testGet1' );
		$cachePriv->targets->set( $res1['id'], $res1['title'] );
		// Second title not in internal cache, resolves from db.
		$res2 = $this->insertPage( 'TitleLocalCacheTest_testGet2' );
		$cache->expects( $this->once() )->method( 'resolve' )
			->with( [ $res2['id'] ] )
			->willReturn( [ $res2['id'] => $res2['title'] ] );

		// Register demand for both
		$cache->add( $res1['id'] );
		$cache->add( $res2['id'] );

		// Should not call resolve() for first title
		$this->assertSame( $res1['title'], $cache->get( $res1['id'] ), 'First title' );

		// Should resolve() for second title
		$this->assertSame( $res2['title'], $cache->get( $res2['id'] ), 'Second title' );
	}

	public function testClearAll() {
		$cache = $this->mockTitleLocalCache();

		// Add 1 to cache
		$cachePriv = TestingAccessWrapper::newFromObject( $cache );
		$cachePriv->targets->set( 1, $this->mockTitle() );
		// Add 2 and 3 to demand
		$cache->add( 2 );
		$cache->add( 3 );
		$cache->clearAll();

		$this->assertNull( $cache->get( 1 ), 'Cache was cleared' );

		// Lookups batch was cleared
		$cache->expects( $this->once() )->method( 'resolve' )
			->with( [ 4 ] )
			->willReturn( [] );
		$cache->add( 4 );
		$cache->get( 4 );
	}

	/**
	 * @return Title
	 */
	protected function mockTitle() {
		$title = $this->createMock( Title::class );

		return $title;
	}

	private function mockTitleLocalCache(): TitleLocalCache {
		$services = $this->getServiceContainer();
		return $this->getMockBuilder( TitleLocalCache::class )
			->setConstructorArgs( [
				$services->getPageStore(),
				$services->getTitleFactory()
			] )
			->onlyMethods( [ 'resolve' ] )->getMock();
	}
}

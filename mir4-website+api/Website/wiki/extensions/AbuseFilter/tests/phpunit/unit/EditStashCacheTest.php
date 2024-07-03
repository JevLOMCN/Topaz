<?php

namespace MediaWiki\Extension\AbuseFilter\Tests\Unit;

use HashBagOStuff;
use MediaWiki\Extension\AbuseFilter\EditStashCache;
use MediaWiki\Extension\AbuseFilter\KeywordsManager;
use MediaWiki\Extension\AbuseFilter\Variables\LazyVariableComputer;
use MediaWiki\Extension\AbuseFilter\Variables\VariableHolder;
use MediaWiki\Extension\AbuseFilter\Variables\VariablesManager;
use MediaWiki\Title\TitleValue;
use MediaWikiUnitTestCase;
use NullStatsdDataFactory;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \MediaWiki\Extension\AbuseFilter\EditStashCache
 * @covers ::__construct
 */
class EditStashCacheTest extends MediaWikiUnitTestCase {

	private function getVariablesManager(): VariablesManager {
		return new VariablesManager(
			$this->createMock( KeywordsManager::class ),
			$this->createMock( LazyVariableComputer::class )
		);
	}

	/**
	 * @covers ::store
	 * @covers ::logCache
	 * @covers ::getStashKey
	 */
	public function testStore() {
		$title = new TitleValue( NS_MAIN, 'Some title' );
		$cache = $this->getMockBuilder( HashBagOStuff::class )
			->onlyMethods( [ 'set' ] )
			->getMock();
		$cache->expects( $this->once() )->method( 'set' );
		$logger = $this->createMock( LoggerInterface::class );
		$logger->expects( $this->once() )
			->method( 'debug' )
			->willReturnCallback( function ( $msg, $args ) use ( $title ) {
				unset( $args['key'] );
				$this->assertSame( [ 'logtype' => 'store', 'target' => $title ], $args );
			} );
		$stash = new EditStashCache(
			$cache,
			new NullStatsdDataFactory(),
			$this->getVariablesManager(),
			$logger,
			$title,
			'default'
		);
		$vars = VariableHolder::newFromArray( [ 'page_title' => 'Title' ] );
		$data = [ 'foo' => 'bar' ];
		$stash->store( $vars, $data );
		$this->addToAssertionCount( 1 );
	}

	public static function provideRoundTrip() {
		$simple = [ 'page_title' => 'Title', 'new_wikitext' => 'Foo Bar' ];
		yield 'simple' => [ $simple, $simple ];
		yield 'noisy' => [
			$simple + [ 'user_age' => 100 ],
			$simple + [ 'user_age' => 200 ],
		];
		$reverse = [
			'new_wikitext' => $simple['new_wikitext'],
			'page_title' => $simple['page_title'],
		];
		yield 'different order' => [
			$reverse + [ 'page_age' => 100 ],
			$reverse + [ 'page_age' => 200 ],
		];
	}

	/**
	 * @covers ::store
	 * @covers ::logCache
	 * @covers ::seek
	 * @covers ::getStashKey
	 * @dataProvider provideRoundTrip
	 */
	public function testRoundTrip( array $storeVars, array $seekVars ) {
		$title = new TitleValue( NS_MAIN, 'Some title' );
		$cache = new HashBagOStuff();
		$logger = $this->createMock( LoggerInterface::class );
		$storeLogged = false;
		$logger->expects( $this->exactly( 2 ) )
			->method( 'debug' )
			->willReturnCallback( function ( $msg, $args ) use ( $title, &$storeLogged ) {
				unset( $args['key'] );
				if ( !$storeLogged ) {
					$this->assertSame( [ 'logtype' => 'store', 'target' => $title ], $args );
					$storeLogged = true;
				} else {
					$this->assertSame( [ 'logtype' => 'hit', 'target' => $title ], $args );
				}
			} );
		$stash = new EditStashCache(
			$cache,
			new NullStatsdDataFactory(),
			$this->getVariablesManager(),
			$logger,
			$title,
			'default'
		);
		$storeHolder = VariableHolder::newFromArray( $storeVars );
		$data = [ 'foo' => 'bar' ];
		$stash->store( $storeHolder, $data );

		$seekHolder = VariableHolder::newFromArray( $seekVars );
		$value = $stash->seek( $seekHolder );
		$this->assertArrayEquals( $data, $value );
	}

	/**
	 * @covers ::seek
	 * @covers ::logCache
	 * @covers ::getStashKey
	 */
	public function testSeek_miss() {
		$title = new TitleValue( NS_MAIN, 'Some title' );
		$cache = new HashBagOStuff();
		$logger = $this->createMock( LoggerInterface::class );
		$logger->expects( $this->once() )
			->method( 'debug' )
			->willReturnCallback( function ( $msg, $args ) use ( $title ) {
				unset( $args['key'] );
				$this->assertSame( [ 'logtype' => 'miss', 'target' => $title ], $args );
			} );
		$stash = new EditStashCache(
			$cache,
			new NullStatsdDataFactory(),
			$this->getVariablesManager(),
			$logger,
			$title,
			'default'
		);
		$vars = VariableHolder::newFromArray( [ 'page_title' => 'Title' ] );
		$value = $stash->seek( $vars );
		$this->assertFalse( $value );
	}

}

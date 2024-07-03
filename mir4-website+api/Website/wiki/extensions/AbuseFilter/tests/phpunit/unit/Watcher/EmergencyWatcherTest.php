<?php

namespace MediaWiki\Extension\AbuseFilter\Tests\Unit\Watcher;

use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\AbuseFilter\EchoNotifier;
use MediaWiki\Extension\AbuseFilter\EmergencyCache;
use MediaWiki\Extension\AbuseFilter\Filter\ExistingFilter;
use MediaWiki\Extension\AbuseFilter\FilterLookup;
use MediaWiki\Extension\AbuseFilter\Watcher\EmergencyWatcher;
use MediaWiki\Utils\MWTimestamp;
use MediaWikiUnitTestCase;
use Wikimedia\Rdbms\LBFactory;

/**
 * @coversDefaultClass \MediaWiki\Extension\AbuseFilter\Watcher\EmergencyWatcher
 */
class EmergencyWatcherTest extends MediaWikiUnitTestCase {

	private function getOptions(): ServiceOptions {
		return new ServiceOptions(
			EmergencyWatcher::CONSTRUCTOR_OPTIONS,
			[
				'AbuseFilterEmergencyDisableAge' => [
					'default' => 86400,
					'other' => 3600,
				],
				'AbuseFilterEmergencyDisableCount' => [
					'default' => 2,
				],
				'AbuseFilterEmergencyDisableThreshold' => [
					'default' => 0.05,
					'other' => 0.01,
				],
			]
		);
	}

	private function getEmergencyCache( array $cacheData, string $group ): EmergencyCache {
		$cache = $this->createMock( EmergencyCache::class );
		$cache->method( 'getForFilter' )
			->with( 1 )
			->willReturn( $cacheData );
		$cache->method( 'getFiltersToCheckInGroup' )
			->with( $group )
			->willReturn( [ 1 ] );
		return $cache;
	}

	private function getFilterLookup( array $filterData ): FilterLookup {
		$lookup = $this->createMock( FilterLookup::class );
		$lookup->method( 'getFilter' )
			->with( 1, false )
			->willReturnCallback( function () use ( $filterData ) {
				$filterObj = $this->createMock( ExistingFilter::class );
				$filterObj->method( 'getTimestamp' )->willReturn( $filterData['timestamp'] );
				$filterObj->method( 'isThrottled' )->willReturn( $filterData['throttled'] ?? false );
				return $filterObj;
			} );
		return $lookup;
	}

	public static function provideFiltersToThrottle(): array {
		return [
			'throttled, default group' => [
				/* timestamp */ '20201016010000',
				/* filterData */ [
					'timestamp' => '20201016000000'
				],
				/* cacheData */ [
					'total' => 100,
					'matches' => 10
				],
				/* willThrottle */ true
			],
			'throttled, other group' => [
				/* timestamp */ '20201016003000',
				/* filterData */ [
					'timestamp' => '20201016000000'
				],
				/* cacheData */ [
					'total' => 100,
					'matches' => 5
				],
				/* willThrottle */ true,
				/* group */ 'other'
			],
			'not throttled, already is' => [
				/* timestamp */ '20201016010000',
				/* filterData */ [
					'timestamp' => '20201016000000',
					'throttled' => true,
				],
				/* cacheData */ [
					'total' => 100,
					'matches' => 10
				],
				/* willThrottle */ false
			],
			'not throttled, not enough actions' => [
				/* timestamp */ '20201016010000',
				/* filterData */ [
					'timestamp' => '20201016000000'
				],
				/* cacheData */ [
					'total' => 5,
					'matches' => 2
				],
				/* willThrottle */ false
			],
			'not throttled, too few matches' => [
				/* timestamp */ '20201016010000',
				/* filterData */ [
					'timestamp' => '20201016000000'
				],
				/* cacheData */ [
					'total' => 100,
					'matches' => 5
				],
				/* willThrottle */ false
			],
			'not throttled, too long period' => [
				/* timestamp */ '20201017010000',
				/* filterData */ [
					'timestamp' => '20201016000000'
				],
				/* cacheData */ [
					'total' => 1000,
					'matches' => 100
				],
				/* willThrottle */ false
			],
			'not throttled, profiler reset' => [
				/* timestamp */ '20201016010000',
				/* filterData */ [
					'timestamp' => '20201016000000'
				],
				/* cacheData */ [
					'total' => 0,
					'matches' => 0
				],
				/* willThrottle */ false
			],
		];
	}

	/**
	 * @covers ::getFiltersToThrottle
	 * @covers ::getEmergencyValue
	 * @dataProvider provideFiltersToThrottle
	 */
	public function testGetFiltersToThrottle(
		string $timestamp,
		array $filterData,
		array $cacheData,
		bool $willThrottle,
		string $group = 'default'
	) {
		MWTimestamp::setFakeTime( $timestamp );
		$watcher = new EmergencyWatcher(
			$this->getEmergencyCache( $cacheData, $group ),
			$this->createMock( LBFactory::class ),
			$this->getFilterLookup( $filterData ),
			$this->createMock( EchoNotifier::class ),
			$this->getOptions()
		);
		$toThrottle = $watcher->getFiltersToThrottle(
			[ 1 ],
			$group
		);
		$this->assertSame(
			$willThrottle ? [ 1 ] : [],
			$toThrottle
		);
	}

	/**
	 * @covers ::__construct
	 */
	public function testConstruct() {
		$watcher = new EmergencyWatcher(
			$this->createMock( EmergencyCache::class ),
			$this->createMock( LBFactory::class ),
			$this->createMock( FilterLookup::class ),
			$this->createMock( EchoNotifier::class ),
			$this->getOptions()
		);
		$this->assertInstanceOf( EmergencyWatcher::class, $watcher );
	}
}

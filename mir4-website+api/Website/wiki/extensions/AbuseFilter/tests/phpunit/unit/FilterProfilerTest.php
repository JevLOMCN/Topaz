<?php

namespace MediaWiki\Extension\AbuseFilter\Tests\Unit;

use HashBagOStuff;
use IBufferingStatsdDataFactory;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\AbuseFilter\FilterProfiler;
use MediaWiki\Title\Title;
use MediaWikiUnitTestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use TestLogger;
use Wikimedia\WRStats\BagOStuffStatsStore;
use Wikimedia\WRStats\WRStatsFactory;

/**
 * @coversDefaultClass \MediaWiki\Extension\AbuseFilter\FilterProfiler
 * @covers ::__construct
 */
class FilterProfilerTest extends MediaWikiUnitTestCase {

	private const NULL_FILTER_PROFILE = [
		'count' => 0,
		'matches' => 0,
		'total-time' => 0.0,
		'total-cond' => 0,
	];

	private const NULL_GROUP_PROFILE = [
		'total' => 0,
		'overflow' => 0,
		'total-time' => 0.0,
		'total-cond' => 0,
		'matches' => 0,
	];

	private function getFilterProfiler( LoggerInterface $logger = null ): FilterProfiler {
		$options = [
			'AbuseFilterConditionLimit' => 1000,
			'AbuseFilterSlowFilterRuntimeLimit' => 500,
		];
		return new FilterProfiler(
			new WRStatsFactory( new BagOStuffStatsStore( new HashBagOStuff() ) ),
			new ServiceOptions( FilterProfiler::CONSTRUCTOR_OPTIONS, $options ),
			'wiki',
			$this->createMock( IBufferingStatsdDataFactory::class ),
			$logger ?: new NullLogger()
		);
	}

	/**
	 * @covers ::getFilterProfile
	 */
	public function testGetFilterProfile_noData() {
		$profiler = $this->getFilterProfiler();
		$this->assertSame( self::NULL_FILTER_PROFILE, $profiler->getFilterProfile( 1 ) );
	}

	/**
	 * @covers ::getFilterProfile
	 * @covers ::recordPerFilterProfiling
	 * @covers ::recordProfilingResult
	 * @covers ::filterProfileKey
	 */
	public function testGetFilterProfile() {
		$profiler = $this->getFilterProfiler();
		$profiler->recordPerFilterProfiling(
			$this->createMock( Title::class ),
			[
				'1' => [
					'time' => 12.3,
					'conds' => 5,
					'result' => false
				],
			]
		);
		$this->assertSame(
			[
				'count' => 1,
				'matches' => 0,
				'total-time' => 12.3,
				'total-cond' => 5
			],
			$profiler->getFilterProfile( 1 )
		);
	}

	/**
	 * @covers ::getFilterProfile
	 * @covers ::recordPerFilterProfiling
	 * @covers ::recordProfilingResult
	 */
	public function testRecordPerFilterProfiling_mergesResults() {
		$profiler = $this->getFilterProfiler();
		$profiler->recordPerFilterProfiling(
			$this->createMock( Title::class ),
			[
				'1' => [
					'time' => 12.5,
					'conds' => 5,
					'result' => false
				],
			]
		);
		$profiler->recordPerFilterProfiling(
			$this->createMock( Title::class ),
			[
				'1' => [
					'time' => 34.5,
					'conds' => 3,
					'result' => true
				],
			]
		);
		$this->assertSame(
			[
				'count' => 2,
				'matches' => 1,
				'total-time' => 47.0,
				'total-cond' => 8
			],
			$profiler->getFilterProfile( 1 )
		);
	}

	/**
	 * @covers ::recordPerFilterProfiling
	 * @covers ::recordProfilingResult
	 * @covers ::recordSlowFilter
	 */
	public function testRecordPerFilterProfiling_reportsSlowFilter() {
		$logger = new TestLogger();
		$logger->setCollect( true );
		$title = $this->createMock( Title::class );
		$title->method( 'getPrefixedText' )->willReturn( 'title' );

		$profiler = $this->getFilterProfiler( $logger );
		$profiler->recordPerFilterProfiling(
			$title,
			[
				'1' => [
					'time' => 501,
					'conds' => 20,
					'result' => false
				],
			]
		);

		$found = false;
		foreach ( $logger->getBuffer() as [ , $entry ] ) {
			$check = preg_match(
				"/^Edit filter .+ on .+ is taking longer than expected$/",
				$entry
			);
			if ( $check ) {
				$found = true;
				break;
			}
		}
		$this->assertTrue(
			$found,
			"Test that FilterProfiler logs the slow filter."
		);
	}

	/**
	 * @covers ::resetFilterProfile
	 */
	public function testResetFilterProfile() {
		$profiler = $this->getFilterProfiler();
		$profiler->recordPerFilterProfiling(
			$this->createMock( Title::class ),
			[
				'1' => [
					'time' => 12.5,
					'conds' => 5,
					'result' => false
				],
				'2' => [
					'time' => 34.5,
					'conds' => 3,
					'result' => true
				],
			]
		);
		$profiler->resetFilterProfile( 1 );
		$this->assertSame( self::NULL_FILTER_PROFILE, $profiler->getFilterProfile( 1 ) );
		$this->assertNotSame( self::NULL_FILTER_PROFILE, $profiler->getFilterProfile( 2 ) );
	}

	/**
	 * @covers ::recordStats
	 * @covers ::getGroupProfile
	 * @covers ::filterProfileGroupKey
	 */
	public function testGetGroupProfile_noData() {
		$profiler = $this->getFilterProfiler();
		$this->assertSame( self::NULL_GROUP_PROFILE, $profiler->getGroupProfile( 'default' ) );
	}

	/**
	 * @param int $condsUsed
	 * @param float $time
	 * @param bool $matches
	 * @param array $expected
	 * @covers ::recordStats
	 * @covers ::getGroupProfile
	 * @covers ::filterProfileGroupKey
	 * @dataProvider provideRecordStats
	 */
	public function testRecordStats( int $condsUsed, float $time, bool $matches, array $expected ) {
		$profiler = $this->getFilterProfiler();
		$group = 'default';
		$profiler->recordStats( $group, $condsUsed, $time, $matches );
		$this->assertSame( $expected, $profiler->getGroupProfile( $group ) );
	}

	public static function provideRecordStats(): array {
		return [
			'No overflow' => [
				100,
				333.3,
				true,
				[
					'total' => 1,
					'overflow' => 0,
					'total-time' => 333.3,
					'total-cond' => 100,
					'matches' => 1
				]
			],
			'Overflow' => [
				10000,
				20,
				true,
				[
					'total' => 1,
					'overflow' => 1,
					'total-time' => 20.0,
					'total-cond' => 10000,
					'matches' => 1
				]
			]
		];
	}

	/**
	 * @covers ::recordStats
	 * @covers ::getGroupProfile
	 */
	public function testRecordStats_mergesResults() {
		$profiler = $this->getFilterProfiler();
		$profiler->recordStats( 'default', 100, 256.5, true );
		$profiler->recordStats( 'default', 200, 512.5, false );
		$this->assertSame(
			[
				'total' => 2,
				'overflow' => 0,
				'total-time' => 769.0,
				'total-cond' => 300,
				'matches' => 1
			],
			$profiler->getGroupProfile( 'default' )
		);
	}

}

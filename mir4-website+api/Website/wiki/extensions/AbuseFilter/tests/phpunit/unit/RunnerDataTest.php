<?php

namespace MediaWiki\Extension\AbuseFilter\Tests\Unit;

use LogicException;
use MediaWiki\Extension\AbuseFilter\Parser\Exception\UserVisibleWarning;
use MediaWiki\Extension\AbuseFilter\Parser\RuleCheckerStatus;
use MediaWiki\Extension\AbuseFilter\RunnerData;
use MediaWikiUnitTestCase;

/**
 * @coversDefaultClass \MediaWiki\Extension\AbuseFilter\RunnerData
 */
class RunnerDataTest extends MediaWikiUnitTestCase {

	/**
	 * @covers ::__construct()
	 * @covers ::getMatchesMap
	 * @covers ::getProfilingData
	 * @covers ::getTotalRuntime
	 * @covers ::getTotalConditions
	 */
	public function testRunnerData_empty() {
		$runnerData = new RunnerData();
		$this->assertSame( [], $runnerData->getMatchesMap() );
		$this->assertSame( [], $runnerData->getProfilingData() );
		$this->assertSame( 0, $runnerData->getTotalConditions() );
		$this->assertSame( 0.0, $runnerData->getTotalRuntime() );
	}

	/**
	 * @covers ::record
	 * @covers ::getMatchesMap
	 * @covers ::getProfilingData
	 * @covers ::getTotalRuntime
	 * @covers ::getTotalConditions
	 */
	public function testRecord() {
		$runnerData = new RunnerData();
		$runnerData->record(
			1, false,
			new RuleCheckerStatus( true, false, null, [], 7 ),
			12.3
		);
		$runnerData->record(
			1, true,
			new RuleCheckerStatus( false, false, null, [], 5 ),
			23.4
		);

		$this->assertArrayEquals(
			[ '1' => true, 'global-1' => false ],
			$runnerData->getMatchesMap(),
			false,
			true
		);
		$this->assertArrayEquals(
			[
				'1' => [ 'time' => 12.3, 'conds' => 7, 'result' => true ],
				'global-1' => [ 'time' => 23.4, 'conds' => 5, 'result' => false ],
			],
			$runnerData->getProfilingData(),
			false,
			true
		);
		$this->assertSame( 12, $runnerData->getTotalConditions() );
		$this->assertSame( 35.7, $runnerData->getTotalRuntime() );
	}

	/**
	 * @covers ::record
	 */
	public function testRecord_throwsOnSameFilter() {
		$runnerData = new RunnerData();
		$runnerData->record(
			1, false,
			new RuleCheckerStatus( true, false, null, [], 7 ),
			12.3
		);
		$this->expectException( LogicException::class );
		$runnerData->record(
			1, false,
			new RuleCheckerStatus( false, false, null, [], 5 ),
			23.4
		);
	}

	/**
	 * @covers ::__construct
	 * @covers ::toArray
	 * @covers ::fromArray
	 */
	public function testToArrayRoundTrip() {
		$runnerData = new RunnerData();
		$runnerData->record(
			1, false,
			new RuleCheckerStatus(
				true,
				false,
				null,
				[ new UserVisibleWarning( 'match-empty-regex', 3, [] ) ],
				7
			),
			12.3
		);
		$runnerData->record(
			1, true,
			new RuleCheckerStatus( false, false, null, [], 5 ),
			23.4
		);
		$newData = RunnerData::fromArray( $runnerData->toArray() );
		$this->assertSame( $runnerData->getTotalConditions(), $newData->getTotalConditions() );
		$this->assertSame( $runnerData->getTotalRuntime(), $newData->getTotalRuntime() );
		$this->assertSame( $runnerData->getProfilingData(), $newData->getProfilingData() );
		$this->assertSame( $runnerData->getMatchesMap(), $newData->getMatchesMap() );
	}

	/**
	 * @covers ::getAllFilters
	 * @covers ::getMatchedFilters
	 */
	public function testGetAllAndMatchedFilters() {
		$runnerData = new RunnerData();
		$runnerData->record(
			1, false,
			new RuleCheckerStatus( true, false, null, [], 7 ),
			12.3
		);
		$runnerData->record(
			1, true,
			new RuleCheckerStatus( false, false, null, [], 5 ),
			23.4
		);
		$runnerData->record(
			3, false,
			new RuleCheckerStatus( false, false, null, [], 7 ),
			12.3
		);
		$runnerData->record(
			3, true,
			new RuleCheckerStatus( true, false, null, [], 5 ),
			23.4
		);

		$this->assertArrayEquals(
			[ '1', 'global-1', '3', 'global-3' ],
			$runnerData->getAllFilters(),
			false
		);
		$this->assertArrayEquals(
			[ '1', 'global-3' ],
			$runnerData->getMatchedFilters(),
			false
		);
	}

}

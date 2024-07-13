<?php

namespace MediaWiki\Extension\AbuseFilter\Tests\Integration;

use Generator;
use MediaWiki\Extension\AbuseFilter\Maintenance\SearchFilters;
use MediaWiki\Tests\Maintenance\MaintenanceBaseTestCase;

/**
 * @group Test
 * @group AbuseFilter
 * @group Database
 * @covers \MediaWiki\Extension\AbuseFilter\Maintenance\SearchFilters
 */
class SearchFiltersTest extends MaintenanceBaseTestCase {

	protected function setUp(): void {
		global $wgDBtype;

		parent::setUp();

		if ( $wgDBtype !== 'mysql' ) {
			$this->markTestSkipped( 'The script only works on MySQL' );
		}
	}

	/**
	 * @inheritDoc
	 */
	protected function getMaintenanceClass() {
		return SearchFilters::class;
	}

	/**
	 * @inheritDoc
	 */
	public function addDBData() {
		$defaultRow = [
			'af_user' => 0,
			'af_user_text' => 'FilterTester',
			'af_actor' => 1,
			'af_timestamp' => $this->db->timestamp( '20190826000000' ),
			'af_enabled' => 1,
			'af_comments' => '',
			'af_public_comments' => 'Test filter',
			'af_hidden' => 0,
			'af_hit_count' => 0,
			'af_throttled' => 0,
			'af_deleted' => 0,
			'af_actions' => '',
			'af_global' => 0,
			'af_group' => 'default'
		];
		$rows = [
			[ 'af_id' => 1, 'af_pattern' => '' ] + $defaultRow,
			[ 'af_id' => 2, 'af_pattern' => 'rmspecials(page_title) === "foo"' ] + $defaultRow,
			[ 'af_id' => 3, 'af_pattern' => 'user_editcount % 3 !== 1' ] + $defaultRow,
			[ 'af_id' => 4, 'af_pattern' => 'rmspecials(added_lines_pst) !== ""' ] + $defaultRow
		];
		$this->db->insert( 'abuse_filter', $rows, __METHOD__ );
	}

	private function getExpectedOutput( array $ids, bool $withHeader = true ): string {
		global $wgDBname;
		$expected = $withHeader ? "wiki\tfilter\n" : '';
		foreach ( $ids as $id ) {
			$expected .= "$wgDBname\t$id\n";
		}
		return $expected;
	}

	public static function provideSearches(): Generator {
		yield 'single filter' => [ 'page_title', [ 2 ] ];
		yield 'multiple filters' => [ 'rmspecials', [ 2, 4 ] ];
		yield 'regex' => [ '[a-z]\(', [ 2, 4 ] ];
	}

	/**
	 * @param string $pattern
	 * @param array $expectedIDs
	 * @dataProvider provideSearches
	 */
	public function testExecute_singleWiki( string $pattern, array $expectedIDs ) {
		$this->setMwGlobals( [ 'wgConf' => (object)[ 'wikis' => [] ] ] );
		$this->maintenance->loadParamsAndArgs( null, [ 'pattern' => $pattern ] );
		$this->expectOutputString( $this->getExpectedOutput( $expectedIDs ) );
		$this->maintenance->execute();
	}

	/**
	 * @param string $pattern
	 * @param array $expectedIDs
	 * @dataProvider provideSearches
	 */
	public function testExecute_multipleWikis( string $pattern, array $expectedIDs ) {
		global $wgDBname;
		$this->setMwGlobals( [ 'wgConf' => (object)[ 'wikis' => [ $wgDBname, $wgDBname ] ] ] );
		$this->maintenance->loadParamsAndArgs( null, [ 'pattern' => $pattern ] );
		$expectedText = $this->getExpectedOutput( $expectedIDs ) . $this->getExpectedOutput( $expectedIDs, false );
		$this->expectOutputString( $expectedText );
		$this->maintenance->execute();
	}
}

<?php

namespace MediaWiki\Extension\AbuseFilter\Tests\Unit;

use AbuseFilterRowsAndFiltersTestTrait;
use Generator;
use HashBagOStuff;
use MediaWiki\Extension\AbuseFilter\CentralDBManager;
use MediaWiki\Extension\AbuseFilter\CentralDBNotAvailableException;
use MediaWiki\Extension\AbuseFilter\Filter\ClosestFilterVersionNotFoundException;
use MediaWiki\Extension\AbuseFilter\Filter\ExistingFilter;
use MediaWiki\Extension\AbuseFilter\Filter\Filter;
use MediaWiki\Extension\AbuseFilter\Filter\FilterNotFoundException;
use MediaWiki\Extension\AbuseFilter\Filter\FilterVersionNotFoundException;
use MediaWiki\Extension\AbuseFilter\Filter\Flags;
use MediaWiki\Extension\AbuseFilter\Filter\HistoryFilter;
use MediaWiki\Extension\AbuseFilter\Filter\LastEditInfo;
use MediaWiki\Extension\AbuseFilter\Filter\Specs;
use MediaWiki\Extension\AbuseFilter\FilterLookup;
use MediaWikiUnitTestCase;
use stdClass;
use WANObjectCache;
use Wikimedia\Rdbms\DBConnRef;
use Wikimedia\Rdbms\ILoadBalancer;
use Wikimedia\Rdbms\LBFactory;
use Wikimedia\Rdbms\SelectQueryBuilder;

/**
 * @group Test
 * @group AbuseFilter
 * @coversDefaultClass \MediaWiki\Extension\AbuseFilter\FilterLookup
 * @todo Some integration tests with a real DB might be helpful
 */
class FilterLookupTest extends MediaWikiUnitTestCase {
	use AbuseFilterRowsAndFiltersTestTrait;

	/**
	 * @param DBConnRef|null $db
	 * @param string|false $centralDB
	 * @param WANObjectCache|null $cache
	 * @param bool $filterIsCentral
	 * @return FilterLookup
	 */
	private function getLookup(
		DBConnRef $db = null,
		$centralDB = false,
		WANObjectCache $cache = null,
		bool $filterIsCentral = false
	): FilterLookup {
		$lb = $this->createMock( ILoadBalancer::class );
		$lb->method( 'getConnection' )
			->willReturn( $db ?? $this->createMock( DBConnRef::class ) );

		$lbFactory = $this->createMock( LBFactory::class );
		$lbFactory->method( 'getMainLB' )->willReturnCallback(
			static function ( $domain ) use ( $lb, $centralDB ) {
				// Return null for sanity
				return $domain === $centralDB ? $lb : null;
			}
		);
		$centralDBManager = new CentralDBManager( $lbFactory, $centralDB, $filterIsCentral );
		return new FilterLookup(
			$lb,
			// Cannot use mocks because final methods aren't mocked and they would error out
			$cache ?? new WANObjectCache( [ 'cache' => new HashBagOStuff() ] ),
			$centralDBManager
		);
	}

	/**
	 * Hacky helper to set up the database
	 * @param stdClass[] $filterRows abuse_filter or abuse_filter_history
	 * @param stdClass[] $actionRows
	 * @return DBConnRef
	 */
	private function getDBWithMockRows( array $filterRows, array $actionRows = [] ): DBConnRef {
		$db = $this->createMock( DBConnRef::class );
		$db->method( 'newSelectQueryBuilder' )->willReturnCallback( static fn () => new SelectQueryBuilder( $db ) );
		$db->method( 'selectRow' )->willReturnCallback( static function ( $table ) use ( $filterRows ) {
			$tables = (array)$table;
			return array_intersect( $tables, [ 'abuse_filter', 'abuse_filter_history' ] ) ? $filterRows[0] : false;
		} );
		$db->method( 'select' )->willReturnCallback(
			static function ( $table, $_, $where ) use ( $filterRows, $actionRows ) {
				$tables = (array)$table;
				if ( in_array( 'abuse_filter_action', $tables ) ) {
					$ret = [];
					foreach ( $actionRows as $row ) {
						if ( $row->afa_filter === $where['afa_filter'] ) {
							$ret[] = $row;
						}
					}
					return $ret;
				} elseif ( array_intersect( $tables, [ 'abuse_filter', 'abuse_filter_history' ] ) ) {
					return $filterRows;
				} else {
					return [];
				}
			}
		);
		return $db;
	}

	/**
	 * @param int $version
	 * @param stdClass $dbRow
	 * @param HistoryFilter $expected
	 * @dataProvider provideFilterVersions
	 * @covers ::getFilterVersion
	 * @covers ::filterFromHistoryRow
	 */
	public function testGetFilterVersion( int $version, stdClass $dbRow, HistoryFilter $expected ) {
		$db = $this->getDBWithMockRows( [ $dbRow ] );
		$filterLookup = $this->getLookup( $db );
		$this->assertEquals( $expected, $filterLookup->getFilterVersion( $version ) );
	}

	/**
	 * @return Generator
	 */
	public static function provideFilterVersions(): Generator {
		$version = 163;
		$filters = [
			'no actions' => new HistoryFilter(
				new Specs(
					'false',
					'X',
					'Y',
					[],
					'default'
				),
				new Flags(
					true,
					true,
					true,
					true
				),
				[],
				new LastEditInfo(
					42,
					'FilterManager',
					'20180706142932'
				),
				1,
				$version
			),
			'with actions' => new HistoryFilter(
				new Specs(
					'the_answer := 42; the_answer === 6*9',
					'Some comments',
					'My filter',
					[ 'degroup', 'disallow' ],
					'default'
				),
				new Flags(
					true,
					false,
					true,
					false
				),
				[ 'degroup' => [], 'disallow' => [] ],
				new LastEditInfo(
					42,
					'FilterManager',
					'20180706142932'
				),
				1,
				$version
			),
		];

		foreach ( $filters as $filter ) {
			$flags = [];
			foreach ( [ 'enabled', 'deleted', 'hidden', 'global' ] as $flag ) {
				$method = 'is' . ucfirst( $flag );
				if ( $filter->$method() ) {
					$flags[] = $flag;
				}
			}
			$historyRow = (object)[
				'afh_id' => $version,
				'afh_filter' => $filter->getID(),
				'afh_user' => $filter->getUserID(),
				'afh_user_text' => $filter->getUserName(),
				'afh_timestamp' => $filter->getTimestamp(),
				'afh_pattern' => $filter->getRules(),
				'afh_comments' => $filter->getComments(),
				'afh_flags' => implode( ',', $flags ),
				'afh_public_comments' => $filter->getName(),
				'afh_actions' => serialize( array_fill_keys( $filter->getActionsNames(), [] ) ),
				'afh_deleted' => 0,
				'afh_changed_fields' => 'actions',
				'afh_group' => $filter->getGroup()
			];
			yield [ $version, $historyRow, $filter ];
		}
	}

	/**
	 * @covers ::getFilterVersion
	 */
	public function testGetFilterVersion_notfound() {
		$db = $this->createMock( DBConnRef::class );
		$db->method( 'selectRow' )->willReturn( false );
		$db->method( 'newSelectQueryBuilder' )->willReturnCallback( static fn () => new SelectQueryBuilder( $db ) );
		$filterLookup = $this->getLookup( $db );
		$this->expectException( FilterVersionNotFoundException::class );
		$filterLookup->getFilterVersion( 42 );
	}

	/**
	 * @covers ::getLastHistoryVersion
	 */
	public function testGetLastHistoryVersion() {
		// Reuse this data provider for conveniency
		[ , $historyRow, $filter ] = $this->provideFilterVersions()->current();
		$db = $this->getDBWithMockRows( [ $historyRow ] );
		$filterLookup = $this->getLookup( $db );
		$this->assertEquals( $filter, $filterLookup->getLastHistoryVersion( $filter->getID() ) );
	}

	/**
	 * @covers ::getLastHistoryVersion
	 */
	public function testGetLastHistoryVersion_notfound() {
		$db = $this->createMock( DBConnRef::class );
		$db->method( 'selectRow' )->willReturn( false );
		$db->method( 'newSelectQueryBuilder' )->willReturnCallback( static fn () => new SelectQueryBuilder( $db ) );
		$filterLookup = $this->getLookup( $db );
		$this->expectException( FilterNotFoundException::class );
		$filterLookup->getLastHistoryVersion( 42 );
	}

	/**
	 * @covers ::getClosestVersion
	 */
	public function testGetClosestVersion() {
		// Reuse this data provider for conveniency
		[ , $historyRow, $filter ] = $this->provideFilterVersions()->current();
		$db = $this->getDBWithMockRows( [ $historyRow ] );
		$filterLookup = $this->getLookup( $db );
		$this->assertEquals( $filter, $filterLookup->getClosestVersion( 1, 42, FilterLookup::DIR_NEXT ) );
	}

	/**
	 * @covers ::getClosestVersion
	 */
	public function testGetClosestVersion_notfound() {
		$db = $this->createMock( DBConnRef::class );
		$db->method( 'selectRow' )->willReturn( false );
		$db->method( 'newSelectQueryBuilder' )->willReturnCallback( static fn () => new SelectQueryBuilder( $db ) );
		$filterLookup = $this->getLookup( $db );
		$this->expectException( ClosestFilterVersionNotFoundException::class );
		$filterLookup->getClosestVersion( 42, 42, FilterLookup::DIR_PREV );
	}

	/**
	 * @covers ::getFirstFilterVersionID
	 */
	public function testGetFirstFilterVersionID() {
		$versionID = 1234;
		$db = $this->createMock( DBConnRef::class );
		$db->method( 'selectField' )->willReturn( $versionID );
		$db->method( 'newSelectQueryBuilder' )->willReturnCallback( static fn () => new SelectQueryBuilder( $db ) );
		$filterLookup = $this->getLookup( $db );
		$this->assertSame( $versionID, $filterLookup->getFirstFilterVersionID( 42 ) );
	}

	/**
	 * @covers ::getFirstFilterVersionID
	 */
	public function testGetFirstFilterVersionID_notfound() {
		$db = $this->createMock( DBConnRef::class );
		$db->method( 'selectField' )->willReturn( false );
		$db->method( 'newSelectQueryBuilder' )->willReturnCallback( static fn () => new SelectQueryBuilder( $db ) );
		$filterLookup = $this->getLookup( $db );
		$this->expectException( FilterNotFoundException::class );
		$filterLookup->getFirstFilterVersionID( 42 );
	}

	/**
	 * @covers ::getFilter
	 * @covers ::getCacheKey
	 */
	public function testLocalCache() {
		$row = $this->getRowsAndFilters()['no actions']['row'];
		$db = $this->createMock( DBConnRef::class );
		$db->method( 'newSelectQueryBuilder' )->willReturnCallback( static fn () => new SelectQueryBuilder( $db ) );
		$db->expects( $this->once() )->method( 'selectRow' )->willReturn( $row );
		$filterLookup = $this->getLookup( $db );

		// Warm-up cache
		$filterLookup->getFilter( 42, false );
		// This should fail the soft assertion of once()
		$filterLookup->getFilter( 42, false );
	}

	/**
	 * @covers ::clearLocalCache
	 */
	public function testClearLocalCache() {
		$row = $this->getRowsAndFilters()['no actions']['row'];
		$db = $this->createMock( DBConnRef::class );
		$db->method( 'newSelectQueryBuilder' )->willReturnCallback( static fn () => new SelectQueryBuilder( $db ) );
		$db->expects( $this->exactly( 2 ) )->method( 'selectRow' )->willReturn( $row );
		$filterLookup = $this->getLookup( $db );

		// Both calls should result in a query
		$filterLookup->getFilter( 42, false );
		$filterLookup->clearLocalCache();
		$filterLookup->getFilter( 42, false );
	}

	/**
	 * Provider to account for central vs non-central filter DB
	 * @return array
	 */
	public static function provideIsCentral() {
		return [
			'central' => [ true ],
			'not central' => [ false ]
		];
	}

	/**
	 * @param bool $isCentral
	 * @covers ::getAllActiveFiltersInGroup
	 * @covers ::getGlobalRulesKey
	 * @dataProvider provideIsCentral
	 */
	public function testGlobalCache( bool $isCentral ) {
		$row = $this->getRowsAndFilters()['no actions']['row'];
		$db = $this->createMock( DBConnRef::class );
		$db->method( 'newSelectQueryBuilder' )->willReturnCallback( static fn () => new SelectQueryBuilder( $db ) );
		// Should be called twice: once for the filter, once for the actions
		$db->expects( $this->exactly( 2 ) )->method( 'select' )->willReturnOnConsecutiveCalls( [ $row ], [] );
		$filterLookup = $this->getLookup( $db, 'foobar', null, $isCentral );

		// WAN cache is only used for global filters
		$global = true;
		$group = 'foo';

		// Warm-up cache: the following calls must not fail the soft assertion of once()
		$filterLookup->getAllActiveFiltersInGroup( $group, $global );

		// This is covered by the internal cache
		$filterLookup->getAllActiveFiltersInGroup( $group, $global );
		$filterLookup->clearLocalCache();
		// This is covered by the network cache
		$filterLookup->getAllActiveFiltersInGroup( $group, $global );
	}

	/**
	 * @covers ::purgeGroupWANCache
	 * @covers ::getAllActiveFiltersInGroup
	 * @covers ::getGlobalRulesKey
	 */
	public function testFilterLookupClearNetworkCache() {
		$row = $this->getRowsAndFilters()['no actions']['row'];
		$db = $this->createMock( DBConnRef::class );
		$db->method( 'newSelectQueryBuilder' )->willReturnCallback( static fn () => new SelectQueryBuilder( $db ) );
		// 4 calls: row, actions, row, actions
		$db->expects( $this->exactly( 4 ) )
			->method( 'select' )
			->willReturnOnConsecutiveCalls( [ $row ], [], [ $row ], [] );
		$cache = new WANObjectCache( [ 'cache' => new HashBagOStuff() ] );
		$filterLookup = $this->getLookup( $db, 'foobar', $cache );

		// WAN cache is only used for global filters
		$global = true;
		$group = 'foo';

		// Both calls should result in a query
		$filterLookup->getAllActiveFiltersInGroup( $group, $global );
		$filterLookup->clearLocalCache();
		$filterLookup->purgeGroupWANCache( $group );
		// Avoid cache hits due to $TINY_POSTIVE (use +10 out of an abundance of caution)
		$fakeTime = microtime( true ) + 10;
		$cache->setMockTime( $fakeTime );
		$filterLookup->getAllActiveFiltersInGroup( $group, $global );
	}

	/**
	 * @covers ::__construct
	 */
	public function testValidConstructor() {
		$this->assertInstanceOf(
			FilterLookup::class,
			new FilterLookup(
				$this->createMock( ILoadBalancer::class ),
				$this->createMock( WANObjectCache::class ),
				$this->createMock( CentralDBManager::class )
			)
		);
	}

	/**
	 * @param stdClass $row
	 * @param stdClass[] $actionsRows
	 * @param ExistingFilter $expected
	 * @covers ::getFilter
	 * @covers ::filterFromRow
	 * @covers ::getDBConnection
	 * @covers ::getActionsFromDB
	 * @dataProvider getRowsAndFilters
	 */
	public function testGetFilter( stdClass $row, array $actionsRows, ExistingFilter $expected ) {
		$db = $this->getDBWithMockRows( [ $row ], $actionsRows );
		$filterLookup = $this->getLookup( $db );

		$actual = $filterLookup->getFilter( $row->af_id, false );
		// Trigger the lazy-load mechanism
		$actual->getActions();
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @param stdClass $row
	 * @param stdClass[] $actionsRows
	 * @param ExistingFilter $expected
	 * @covers ::getFilter
	 * @covers ::filterFromRow
	 * @covers ::getDBConnection
	 * @covers ::getActionsFromDB
	 * @dataProvider getRowsAndFilters
	 */
	public function testGetFilter_global( stdClass $row, array $actionsRows, ExistingFilter $expected ) {
		$db = $this->getDBWithMockRows( [ $row ], $actionsRows );
		$filterLookup = $this->getLookup( $db, 'central_wiki' );

		$actual = $filterLookup->getFilter( $row->af_id, true );
		// Trigger the lazy-load mechanism
		$actual->getActions();
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @covers ::getFilter
	 */
	public function testGetFilter_notfound() {
		$db = $this->createMock( DBConnRef::class );
		$db->method( 'newSelectQueryBuilder' )->willReturnCallback( static fn () => new SelectQueryBuilder( $db ) );
		$db->method( 'selectRow' )->willReturn( false );
		$filterLookup = $this->getLookup( $db );

		$this->expectException( FilterNotFoundException::class );
		$filterLookup->getFilter( 42, false );
	}

	/**
	 * @covers ::getFilter
	 * @covers ::getDBConnection
	 */
	public function testGetFilter_globaldisabled() {
		$filterLookup = $this->getLookup();
		$this->expectException( CentralDBNotAvailableException::class );
		$filterLookup->getFilter( 42, true );
	}

	/**
	 * @covers ::getAllActiveFiltersInGroup
	 * @covers ::getAllActiveFiltersInGroupFromDB
	 */
	public function testGetAllActiveFiltersInGroup() {
		$data = $this->getRowsAndFilters();
		$db = $this->getDBWithMockRows(
			array_column( $data, 'row' ),
			array_merge( ...array_column( $data, 'actions' ) )
		);
		$filterLookup = $this->getLookup( $db, false, null );

		$expected = [];
		/** @var Filter $filter */
		foreach ( array_column( $data, 'filter' ) as $filter ) {
			$expected[$filter->getID()] = $filter;
		}

		$actual = $filterLookup->getAllActiveFiltersInGroup( '_', false );
		foreach ( $actual as $filter ) {
			// Trigger the lazy-load mechanism
			$filter->getActions();
		}

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @param bool $isCentral
	 * @covers ::getAllActiveFiltersInGroup
	 * @covers ::getAllActiveFiltersInGroupFromDB
	 * @covers ::getGlobalRulesKey
	 * @dataProvider provideIsCentral
	 */
	public function testGetAllActiveFiltersInGroup_global( bool $isCentral ) {
		$data = $this->getRowsAndFilters();
		$db = $this->getDBWithMockRows(
			array_column( $data, 'row' ),
			array_merge( ...array_column( $data, 'actions' ) )
		);
		$filterLookup = $this->getLookup( $db, 'some_db', null, $isCentral );

		$expected = [];
		/** @var Filter $filter */
		foreach ( array_column( $data, 'filter' ) as $filter ) {
			$key = 'global-' . $filter->getID();
			$expected[$key] = $filter;
		}

		$actual = $filterLookup->getAllActiveFiltersInGroup( '_', true );
		foreach ( $actual as $filter ) {
			// Trigger the lazy-load mechanism
			$filter->getActions();
		}

		$this->assertEquals( $expected, $actual );
	}
}

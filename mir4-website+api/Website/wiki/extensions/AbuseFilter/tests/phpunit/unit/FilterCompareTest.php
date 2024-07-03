<?php

namespace MediaWiki\Extension\AbuseFilter\Tests\Unit;

use MediaWiki\Extension\AbuseFilter\Consequences\ConsequencesRegistry;
use MediaWiki\Extension\AbuseFilter\Filter\Filter;
use MediaWiki\Extension\AbuseFilter\Filter\Flags;
use MediaWiki\Extension\AbuseFilter\Filter\LastEditInfo;
use MediaWiki\Extension\AbuseFilter\Filter\Specs;
use MediaWiki\Extension\AbuseFilter\FilterCompare;
use MediaWiki\Extension\AbuseFilter\Hooks\AbuseFilterHookRunner;
use MediaWikiUnitTestCase;

/**
 * @group Test
 * @group AbuseFilter
 * @coversDefaultClass \MediaWiki\Extension\AbuseFilter\FilterCompare
 */
class FilterCompareTest extends MediaWikiUnitTestCase {
	/**
	 * @param Filter $firstVersion
	 * @param Filter $secondVersion
	 * @param array $expected The differences
	 * @covers ::compareVersions
	 * @dataProvider provideVersions
	 */
	public function testCompareVersions(
		Filter $firstVersion,
		Filter $secondVersion,
		array $expected
	) {
		$allActions = [
			'throttle', 'warn', 'disallow', 'blockautopromote', 'block', 'rangeblock', 'degroup', 'tag'
		];
		$allActions = array_fill_keys( $allActions, true );
		$registry = new ConsequencesRegistry( $this->createMock( AbuseFilterHookRunner::class ), $allActions );
		$compare = new FilterCompare( $registry );

		$this->assertSame( $expected, $compare->compareVersions( $firstVersion, $secondVersion ) );
	}

	/**
	 * Data provider for testCompareVersions
	 * @return array
	 */
	public static function provideVersions() {
		$baseSpecs = [
			'actions' => [],
			'user' => 1,
			'user_text' => 'Foo',
			'timestamp' => '20181016155634',
			'id' => 42
		];
		$makeFilter = static function ( $specs ) use ( $baseSpecs ) {
			$specs += $baseSpecs;
			return new Filter(
				new Specs(
					$specs['rules'],
					$specs['comments'],
					$specs['name'],
					array_keys( $specs['actions'] ),
					$specs['group']
				),
				new Flags(
					$specs['enabled'],
					$specs['deleted'],
					$specs['hidden'],
					$specs['global']
				),
				$specs['actions'],
				new LastEditInfo(
					$specs['user'],
					$specs['user_text'],
					$specs['timestamp']
				),
				$specs['id']
			);
		};

		return [
			[
				$makeFilter( [
					'name' => 'Comments',
					'rules' => '/*Pattern*/',
					'comments' => 'Comments',
					'deleted' => 0,
					'enabled' => 1,
					'hidden' => 0,
					'global' => 0,
					'group' => 'default',
					'actions' => [ 'disallow' => [] ]
				] ),
				$makeFilter( [
					'name' => 'OtherComments',
					'rules' => '/*Other pattern*/',
					'comments' => 'Other comments',
					'deleted' => 1,
					'enabled' => 0,
					'hidden' => 1,
					'global' => 1,
					'group' => 'flow',
					'actions' => [ 'disallow' => [] ]
				] ),
				[
					'af_public_comments',
					'af_pattern',
					'af_comments',
					'af_deleted',
					'af_enabled',
					'af_hidden',
					'af_global',
					'af_group',
				]
			],
			[
				$makeFilter( [
					'name' => 'Comments',
					'rules' => '/*Pattern*/',
					'comments' => 'Comments',
					'deleted' => 0,
					'enabled' => 1,
					'hidden' => 0,
					'global' => 0,
					'group' => 'default',
					'actions' => [ 'disallow' => [] ]
				] ),
				$makeFilter( [
					'name' => 'Comments',
					'rules' => '/*Pattern*/',
					'comments' => 'Comments',
					'deleted' => 0,
					'enabled' => 1,
					'hidden' => 0,
					'global' => 0,
					'group' => 'default',
					'actions' => [ 'disallow' => [] ]
				] ),
				[]
			],
			[
				$makeFilter( [
					'name' => 'Comments',
					'rules' => '/*Pattern*/',
					'comments' => 'Comments',
					'deleted' => 0,
					'enabled' => 1,
					'hidden' => 0,
					'global' => 0,
					'group' => 'default',
					'actions' => [ 'disallow' => [] ]
				] ),
				$makeFilter( [
					'name' => 'Comments',
					'rules' => '/*Pattern*/',
					'comments' => 'Comments',
					'deleted' => 0,
					'enabled' => 1,
					'hidden' => 0,
					'global' => 0,
					'group' => 'default',
					'actions' => [ 'degroup' => [] ]
				] ),
				[ 'actions' ]
			],
			[
				$makeFilter( [
					'name' => 'Comments',
					'rules' => '/*Pattern*/',
					'comments' => 'Comments',
					'deleted' => 0,
					'enabled' => 1,
					'hidden' => 0,
					'global' => 0,
					'group' => 'default',
					'actions' => [ 'disallow' => [] ]
				] ),
				$makeFilter( [
					'name' => 'OtherComments',
					'rules' => '/*Other pattern*/',
					'comments' => 'Other comments',
					'deleted' => 1,
					'enabled' => 0,
					'hidden' => 1,
					'global' => 1,
					'group' => 'flow',
					'actions' => [ 'blockautopromote' => [] ]
				] ),
				[
					'af_public_comments',
					'af_pattern',
					'af_comments',
					'af_deleted',
					'af_enabled',
					'af_hidden',
					'af_global',
					'af_group',
					'actions'
				]
			],
			[
				$makeFilter( [
					'name' => 'Comments',
					'rules' => '/*Pattern*/',
					'comments' => 'Comments',
					'deleted' => 0,
					'enabled' => 1,
					'hidden' => 0,
					'global' => 0,
					'group' => 'default',
					'actions' => [ 'disallow' => [] ]
				] ),
				$makeFilter( [
					'name' => 'Comments',
					'rules' => '/*Pattern*/',
					'comments' => 'Comments',
					'deleted' => 0,
					'enabled' => 1,
					'hidden' => 0,
					'global' => 0,
					'group' => 'default',
					'actions' => [ 'warn' => [ 'abusefilter-warning' ] ]
				] ),
				[ 'actions' ]
			],
			[
				$makeFilter( [
					'name' => 'Comments',
					'rules' => '/*Pattern*/',
					'comments' => 'Comments',
					'deleted' => 0,
					'enabled' => 1,
					'hidden' => 0,
					'global' => 0,
					'group' => 'default',
					'actions' => [ 'warn' => [ 'abusefilter-warning' ] ]
				] ),
				$makeFilter( [
					'name' => 'Comments',
					'rules' => '/*Pattern*/',
					'comments' => 'Comments',
					'deleted' => 0,
					'enabled' => 1,
					'hidden' => 0,
					'global' => 0,
					'group' => 'default',
					'actions' => [ 'disallow' => [] ]
				] ),
				[ 'actions' ]
			],
			[
				$makeFilter( [
					'name' => 'Comments',
					'rules' => '/*Pattern*/',
					'comments' => 'Comments',
					'deleted' => 0,
					'enabled' => 1,
					'hidden' => 0,
					'global' => 0,
					'group' => 'default',
					'actions' => [ 'warn' => [ 'abusefilter-warning' ] ]
				] ),
				$makeFilter( [
					'name' => 'Comments',
					'rules' => '/*Pattern*/',
					'comments' => 'Comments',
					'deleted' => 0,
					'enabled' => 1,
					'hidden' => 0,
					'global' => 0,
					'group' => 'default',
					'actions' => [
						'warn' => [ 'abusefilter-my-best-warning' ],
						'degroup' => []
					]
				] ),
				[ 'actions' ]
			],
			[
				$makeFilter( [
					'name' => 'Comments',
					'rules' => '/*Pattern*/',
					'comments' => 'Comments',
					'deleted' => 0,
					'enabled' => 1,
					'hidden' => 0,
					'global' => 0,
					'group' => 'default',
					'actions' => [ 'warn' => [ 'abusefilter-warning' ] ]
				] ),
				$makeFilter( [
					'name' => 'Comments',
					'rules' => '/*Other Pattern*/',
					'comments' => 'Comments',
					'deleted' => 0,
					'enabled' => 1,
					'hidden' => 1,
					'global' => 0,
					'group' => 'flow',
					'actions' => [ 'warn' => [ 'abusefilter-my-best-warning' ] ]
				] ),
				[
					'af_pattern',
					'af_hidden',
					'af_group',
					'actions'
				]
			],
			[
				$makeFilter( [
					'name' => 'Comments',
					'rules' => '/*Pattern*/',
					'comments' => 'Comments',
					'deleted' => 0,
					'enabled' => 1,
					'hidden' => 0,
					'global' => 0,
					'group' => 'default',
					'actions' => [ 'warn' => [ 'abusefilter-beautiful-warning' ] ]
				] ),
				$makeFilter( [
					'name' => 'Comments',
					'rules' => '/*Pattern*/',
					'comments' => 'Comments',
					'deleted' => 0,
					'enabled' => 1,
					'hidden' => 0,
					'global' => 0,
					'group' => 'flow',
					'actions' => [ 'warn' => [ 'abusefilter-my-best-warning' ] ]
				] ),
				[
					'af_group',
					'actions'
				]
			],
		];
	}

	/**
	 * @covers ::__construct
	 */
	public function testConstruct() {
		$this->assertInstanceOf(
			FilterCompare::class,
			new FilterCompare( $this->createMock( ConsequencesRegistry::class ) )
		);
	}
}

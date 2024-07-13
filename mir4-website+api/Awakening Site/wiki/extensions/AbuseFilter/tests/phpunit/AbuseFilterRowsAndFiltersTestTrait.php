<?php

use MediaWiki\Extension\AbuseFilter\Filter\ExistingFilter;
use MediaWiki\Extension\AbuseFilter\Filter\Flags;
use MediaWiki\Extension\AbuseFilter\Filter\LastEditInfo;
use MediaWiki\Extension\AbuseFilter\Filter\Specs;

/**
 * This trait contains some Filter <-> db_row correspondences, and can be used to avoid long test
 * methods.
 */
trait AbuseFilterRowsAndFiltersTestTrait {
	/**
	 * @return array[]
	 */
	public function getRowsAndFilters(): array {
		static $ret = null;
		if ( $ret !== null ) {
			return $ret;
		}
		$rows = [
			'no actions' => (object)[
				'af_id' => 42,
				'af_pattern' => 'false',
				'af_user' => 1,
				'af_user_text' => 'FilterTester',
				'af_actor' => 1,
				'af_timestamp' => '20190826000000',
				'af_enabled' => 1,
				'af_comments' => '',
				'af_public_comments' => 'Mock filter',
				'af_hidden' => 0,
				'af_hit_count' => 0,
				'af_throttled' => 0,
				'af_deleted' => 0,
				'af_actions' => '',
				'af_global' => 0,
				'af_group' => 'default'
			],
			'with actions' => (object)[
				'af_id' => 163,
				'af_pattern' => 'false',
				'af_user' => 1,
				'af_user_text' => 'FilterTester',
				'af_actor' => 1,
				'af_timestamp' => '20190826000000',
				'af_enabled' => 1,
				'af_comments' => '',
				'af_public_comments' => 'Mock filter',
				'af_hidden' => 1,
				'af_hit_count' => 0,
				'af_throttled' => 1,
				'af_deleted' => 0,
				'af_actions' => 'disallow,blockautopromote',
				'af_global' => 0,
				'af_group' => 'default'
			]
		];

		foreach ( $rows as $name => $row ) {
			$actionKeys = $row->af_actions ? explode( ',', $row->af_actions ) : [];
			$filter = new ExistingFilter(
				new Specs(
					$row->af_pattern,
					$row->af_comments,
					$row->af_public_comments,
					$actionKeys,
					$row->af_group
				),
				new Flags(
					(bool)$row->af_enabled,
					(bool)$row->af_deleted,
					(bool)$row->af_hidden,
					(bool)$row->af_global
				),
				array_fill_keys( $actionKeys, [] ),
				new LastEditInfo(
					$row->af_user,
					$row->af_user_text,
					$row->af_timestamp
				),
				$row->af_id,
				$row->af_hit_count,
				$row->af_throttled
			);
			$ret[$name] = [
				'row' => $row,
				'actions' => $this->getRowsForActions( $row->af_id, $actionKeys ),
				'filter' => $filter
			];
		}

		// Add special cases that cannot be generalized above
		$ret = array_merge( $ret, [
			'null comments and name' => [
				'row' => (object)[
					'af_id' => 333,
					'af_pattern' => 'false',
					'af_user' => 1,
					'af_user_text' => 'FilterTester',
					'af_actor' => 1,
					'af_timestamp' => '20190826000000',
					'af_enabled' => 1,
					'af_comments' => null,
					'af_public_comments' => null,
					'af_hidden' => 1,
					'af_hit_count' => 100,
					'af_throttled' => 1,
					'af_deleted' => 0,
					'af_actions' => 'warn',
					'af_global' => 0,
					'af_group' => 'default'
				],
				'actions' => $this->getRowsForActions( 333, [ 'warn' ] ),
				'filter' => new ExistingFilter(
					new Specs(
						'false',
						'',
						'',
						[ 'warn' ],
						'default'
					),
					new Flags( true, false, true, false ),
					[ 'warn' => [] ],
					new LastEditInfo(
						1,
						'FilterTester',
						'20190826000000'
					),
					333,
					100,
					1
				)
			],
			'no hitcount and throttled' => [
				'row' => (object)[
					'af_id' => 1000,
					'af_pattern' => '"foo"',
					'af_user' => 1,
					'af_user_text' => 'FilterTester',
					'af_actor' => 1,
					'af_timestamp' => '20190826000000',
					'af_enabled' => 0,
					'af_comments' => 'foo',
					'af_public_comments' => 'bar',
					'af_hidden' => 1,
					'af_deleted' => 1,
					'af_actions' => '',
					'af_global' => 1,
					'af_group' => 'default'
				],
				'actions' => [],
				'filter' => new ExistingFilter(
					new Specs(
						'"foo"',
						'foo',
						'bar',
						[],
						'default'
					),
					new Flags( false, true, true, true ),
					[],
					new LastEditInfo(
						1,
						'FilterTester',
						'20190826000000'
					),
					1000
				)
			],
			'pattern to trim' => [
				'row' => (object)[
					'af_id' => 1000,
					'af_pattern' => "\n\t         0        \t\n",
					'af_user' => 1,
					'af_user_text' => 'FilterTester',
					'af_actor' => 1,
					'af_timestamp' => '20190826000000',
					'af_enabled' => 0,
					'af_comments' => 'foo',
					'af_public_comments' => 'bar',
					'af_hidden' => 1,
					'af_hit_count' => 163,
					'af_throttled' => 1,
					'af_deleted' => 1,
					'af_actions' => '',
					'af_global' => 1,
					'af_group' => 'default'
				],
				'actions' => [],
				'filter' => new ExistingFilter(
					new Specs(
						'0',
						'foo',
						'bar',
						[],
						'default'
					),
					new Flags( false, true, true, true ),
					[],
					new LastEditInfo(
						1,
						'FilterTester',
						'20190826000000'
					),
					1000,
					163,
					1
				)
			],
		] );

		return $ret;
	}

	/**
	 * @param int $id
	 * @param string[] $actions
	 * @return stdClass[]
	 */
	private function getRowsForActions( int $id, array $actions ): array {
		$ret = [];
		foreach ( $actions as $action ) {
			$ret[] = (object)[
				'afa_filter' => $id,
				'afa_consequence' => $action,
				'afa_parameters' => ''
			];
		}
		return $ret;
	}
}

<?php

namespace MediaWiki\Extension\AbuseFilter\Tests\Unit;

use Generator;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\AbuseFilter\Consequences\ConsequencesRegistry;
use MediaWiki\Extension\AbuseFilter\Filter\Filter;
use MediaWiki\Extension\AbuseFilter\Filter\Flags;
use MediaWiki\Extension\AbuseFilter\Filter\LastEditInfo;
use MediaWiki\Extension\AbuseFilter\Filter\MutableFilter;
use MediaWiki\Extension\AbuseFilter\Filter\Specs;
use MediaWiki\Extension\AbuseFilter\FilterImporter;
use MediaWiki\Extension\AbuseFilter\Hooks\AbuseFilterHookRunner;
use MediaWiki\Extension\AbuseFilter\InvalidImportDataException;
use MediaWikiUnitTestCase;

/**
 * @group Test
 * @group AbuseFilter
 * @coversDefaultClass \MediaWiki\Extension\AbuseFilter\FilterImporter
 */
class FilterImporterTest extends MediaWikiUnitTestCase {
	private const GOOD_FILTER_DATA = [
		'rules' => 'foobar',
		'name' => 'foobar',
		'comments' => 'foobar',
		'group' => 'foobar',
		'actions' => [],
		'enabled' => true,
		'deleted' => false,
		'hidden' => true,
		'global' => false
	];

	/**
	 * @param string[]|null $groups
	 * @param bool|null $isCentral
	 * @param string[]|null $actions
	 * @return FilterImporter
	 */
	private function getImporter(
		array $groups = null,
		bool $isCentral = null,
		array $actions = null
	): FilterImporter {
		$actions = array_fill_keys( $actions ?? [ 'warn', 'disallow', 'block' ], true );
		$registry = new ConsequencesRegistry(
			$this->createMock( AbuseFilterHookRunner::class ),
			$actions
		);
		return new FilterImporter(
			new ServiceOptions(
				FilterImporter::CONSTRUCTOR_OPTIONS,
				[
					'AbuseFilterValidGroups' => $groups ?? [ 'default' ],
					'AbuseFilterIsCentral' => $isCentral ?? false,
				]
			),
			$registry
		);
	}

	/**
	 * @covers ::encodeData
	 */
	public function testEncodeData() {
		$importer = $this->getImporter();
		$filter = MutableFilter::newDefault();
		$actions = [ 'disallow' => [] ];
		$this->assertIsString( $importer->encodeData( $filter, $actions ) );
	}

	/**
	 * @param mixed $data
	 * @covers ::decodeData
	 * @covers ::isValidImportData
	 * @dataProvider provideInvalidData
	 */
	public function testDecodeData_invalid( $data ) {
		$importer = $this->getImporter();
		$this->expectException( InvalidImportDataException::class );
		$importer->decodeData( $data );
	}

	public static function provideInvalidData() {
		$cases = [
			'non-object' => 'foo',
			'bad top-level keys' => (object)[ 'foo' => 1 ],
			'non-object data' => (object)[ 'data' => null ],
			'wrong actions type' => (object)[ 'data' => (object)[], 'actions' => true ],
			'wrong data keys' => (object)[
				'data' => (object)[ 'foobar' => 42 ],
				'actions' => []
			],
			'wrong action name' => (object)[
				'data' => (object)self::GOOD_FILTER_DATA,
				'actions' => [ 'unknown' => [] ]
			],
			'non-array action params' => (object)[
				'data' => (object)self::GOOD_FILTER_DATA,
				'actions' => [ 'block' => true ]
			],
		];
		foreach ( $cases as $name => $case ) {
			yield $name => [ json_encode( $case ) ];
		}
	}

	/**
	 * @param Filter $origFilter
	 * @param array $origActions
	 * @param Filter $expectedFilter
	 * @param array $configOptions
	 * @covers ::decodeData
	 * @covers ::encodeData
	 * @covers ::isValidImportData
	 * @dataProvider provideRoundTrip
	 */
	public function testRoundTrip(
		Filter $origFilter,
		array $origActions,
		Filter $expectedFilter,
		array $configOptions
	) {
		$importer = $this->getImporter(
			$configOptions['groups'] ?? null,
			$configOptions['central'] ?? null,
			$configOptions['actions'] ?? null
		);
		$actualFilter = $importer->decodeData( $importer->encodeData( $origFilter, $origActions ) );
		$this->assertEquals( $expectedFilter, $actualFilter );
	}

	/**
	 * @return Generator
	 */
	public static function provideRoundTrip(): Generator {
		$actions = [
			'block' => [],
			'warn' => []
		];
		$filter = new MutableFilter(
			new Specs(
				'rules',
				'comments',
				'name',
				array_keys( $actions ),
				'some-group'
			),
			new Flags(
				true,
				false,
				true,
				true
			),
			$actions,
			new LastEditInfo(
				0,
				'',
				''
			)
		);

		yield 'normal' => [
			$filter,
			$actions,
			$filter,
			[ 'central' => true, 'groups' => [ 'default', 'some-group' ] ]
		];

		$expFilter = clone $filter;
		$expFilter->setGroup( 'default' );
		yield 'group not in use' => [
			$filter,
			$actions,
			$expFilter,
			[ 'central' => true, 'groups' => [ 'default' ] ]
		];

		$expFilter = clone $filter;
		$expFilter->setGlobal( false );
		yield 'cannot create as global' => [
			$filter,
			$actions,
			$expFilter,
			[ 'groups' => [ 'default', 'some-group' ] ]
		];
	}

	/**
	 * @covers ::__construct
	 */
	public function testConstruct() {
		$this->assertInstanceOf(
			FilterImporter::class,
			new FilterImporter(
				$this->createMock( ServiceOptions::class ),
				$this->createMock( ConsequencesRegistry::class )
			)
		);
	}
}

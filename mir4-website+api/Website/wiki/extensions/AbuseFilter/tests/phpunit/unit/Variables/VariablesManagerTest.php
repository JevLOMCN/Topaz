<?php

namespace MediaWiki\Extension\AbuseFilter\Tests\Unit;

use LogicException;
use MediaWiki\Extension\AbuseFilter\Hooks\AbuseFilterHookRunner;
use MediaWiki\Extension\AbuseFilter\KeywordsManager;
use MediaWiki\Extension\AbuseFilter\Parser\AFPData;
use MediaWiki\Extension\AbuseFilter\Variables\LazyLoadedVariable;
use MediaWiki\Extension\AbuseFilter\Variables\LazyVariableComputer;
use MediaWiki\Extension\AbuseFilter\Variables\UnsetVariableException;
use MediaWiki\Extension\AbuseFilter\Variables\VariableHolder;
use MediaWiki\Extension\AbuseFilter\Variables\VariablesManager;
use MediaWikiUnitTestCase;

/**
 * @group Test
 * @group AbuseFilter
 * @coversDefaultClass \MediaWiki\Extension\AbuseFilter\Variables\VariablesManager
 */
class VariablesManagerTest extends MediaWikiUnitTestCase {
	/**
	 * @param LazyVariableComputer|null $lazyComputer
	 * @return VariablesManager
	 */
	private function getManager( LazyVariableComputer $lazyComputer = null ): VariablesManager {
		return new VariablesManager(
			new KeywordsManager( $this->createMock( AbuseFilterHookRunner::class ) ),
			$lazyComputer ?? $this->createMock( LazyVariableComputer::class )
		);
	}

	/**
	 * @covers ::translateDeprecatedVars
	 */
	public function testTranslateDeprecatedVars() {
		$varsMap = [
			'timestamp' => new AFPData( AFPData::DSTRING, '123' ),
			'added_lines' => new AFPData( AFPData::DSTRING, 'foobar' ),
			'article_text' => new AFPData( AFPData::DSTRING, 'FOO' ),
			'article_articleid' => new AFPData( AFPData::DINT, 42 )
		];
		$translatedVarsMap = [
			'timestamp' => $varsMap['timestamp'],
			'added_lines' => $varsMap['added_lines'],
			'page_title' => $varsMap['article_text'],
			'page_id' => $varsMap['article_articleid']
		];
		$holder = VariableHolder::newFromArray( $varsMap );
		$manager = $this->getManager();
		$manager->translateDeprecatedVars( $holder );
		$this->assertEquals( $translatedVarsMap, $holder->getVars() );
	}

	/**
	 * @param VariableHolder $holder
	 * @param array|bool $compute
	 * @param bool $includeUser
	 * @param array $expected
	 * @dataProvider provideDumpAllVars
	 *
	 * @covers ::dumpAllVars
	 */
	public function testDumpAllVars(
		VariableHolder $holder,
		$compute,
		bool $includeUser,
		array $expected
	) {
		$computer = $this->createMock( LazyVariableComputer::class );
		$computer->method( 'compute' )->willReturnCallback(
			static function ( LazyLoadedVariable $var ) {
				switch ( $var->getMethod() ) {
					case 'preftitle':
						return new AFPData( AFPData::DSTRING, 'title' );
					case 'lines':
						return new AFPData( AFPData::DSTRING, 'lines' );
					default:
						throw new LogicException( 'Unrecognized value!' );
				}
			}
		);

		$manager = $this->getManager( $computer );

		$this->assertEquals( $expected, $manager->dumpAllVars( $holder, $compute, $includeUser ) );
	}

	public static function provideDumpAllVars() {
		$titleVal = 'title';
		$preftitle = new LazyLoadedVariable( 'preftitle', [] );

		$linesVal = 'lines';
		$lines = new LazyLoadedVariable( 'lines', [] );

		$pairs = [
			'page_title' => 'foo',
			'page_prefixedtitle' => $preftitle,
			'added_lines' => $lines,
			'user_name' => 'bar',
			'custom-var' => 'foo'
		];
		$vars = VariableHolder::newFromArray( $pairs );

		$nonLazy = array_fill_keys( [ 'page_title', 'user_name', 'custom-var' ], 1 );
		$nonLazyExpect = array_intersect_key( $pairs, $nonLazy );
		yield 'lazy-loaded vars are excluded if not computed' => [
			clone $vars,
			[],
			true,
			$nonLazyExpect
		];

		$nonUserExpect = array_diff_key( $nonLazyExpect, [ 'custom-var' => 1 ] );
		yield 'user-set vars are excluded' => [ clone $vars, [], false, $nonUserExpect ];

		$allExpect = $pairs;
		$allExpect['page_prefixedtitle'] = $titleVal;
		$allExpect['added_lines'] = $linesVal;
		yield 'all vars computed' => [ clone $vars, true, true, $allExpect ];

		$titleOnlyComputed = array_merge( $nonLazyExpect, [ 'page_prefixedtitle' => $titleVal ] );
		yield 'Only a specific var computed' => [
			clone $vars,
			[ 'page_prefixedtitle' ],
			true,
			$titleOnlyComputed
		];
	}

	/**
	 * @covers ::computeDBVars
	 */
	public function testComputeDBVars() {
		$nonDBMet = [ 'unknown', 'certainly-not-db' ];
		$dbMet = [ 'page-age', 'user-age', 'load-recent-authors' ];
		$methods = array_merge( $nonDBMet, $dbMet );
		$objs = [];
		foreach ( $methods as $method ) {
			$cur = new LazyLoadedVariable( $method, [] );
			$objs[$method] = $cur;
		}

		$vars = VariableHolder::newFromArray( $objs );
		$computer = $this->createMock( LazyVariableComputer::class );
		$computer->method( 'compute' )->willReturnCallback(
			static function ( LazyLoadedVariable $var ) {
				return new AFPData( AFPData::DSTRING, $var->getMethod() );
			}
		);
		$varManager = $this->getManager( $computer );
		$varManager->computeDBVars( $vars );

		$expAFCV = array_intersect_key( $vars->getVars(), array_fill_keys( $nonDBMet, 1 ) );
		$this->assertContainsOnlyInstancesOf(
			LazyLoadedVariable::class,
			$expAFCV,
			"non-DB methods shouldn't have been computed"
		);

		$expComputed = array_intersect_key( $vars->getVars(), array_fill_keys( $dbMet, 1 ) );
		$this->assertContainsOnlyInstancesOf(
			AFPData::class,
			$expComputed,
			'DB methods should have been computed'
		);
	}

	/**
	 * @param VariablesManager $manager
	 * @param VariableHolder $holder
	 * @param string $name
	 * @param int $flags
	 * @param AFPData|string $expected String if expecting an exception
	 * @covers ::getVar
	 *
	 * @dataProvider provideGetVar
	 */
	public function testGetVar(
		VariablesManager $manager,
		VariableHolder $holder,
		string $name,
		int $flags,
		$expected
	) {
		if ( is_string( $expected ) ) {
			$this->expectException( $expected );
			$manager->getVar( $holder, $name, $flags );
		} else {
			$this->assertEquals( $expected, $manager->getVar( $holder, $name, $flags ) );
		}
	}

	/**
	 * @todo make static
	 * @return Generator|array
	 */
	public function provideGetVar() {
		$vars = new VariableHolder();

		$name = 'foo';
		$expected = new AFPData( AFPData::DSTRING, 'foobarbaz' );
		$computer = $this->createMock( LazyVariableComputer::class );
		$computer->method( 'compute' )->willReturn( $expected );
		$afcv = new LazyLoadedVariable( '', [] );
		$vars->setVar( $name, $afcv );
		yield 'set, lazy-loaded' => [ $this->getManager( $computer ), $vars, $name, 0, $expected ];

		$alreadySetName = 'first-var';
		$firstValue = new AFPData( AFPData::DSTRING, 'expected value' );
		$setVars = VariableHolder::newFromArray( [ 'first-var' => $firstValue ] );
		$computer = $this->createMock( LazyVariableComputer::class );
		$computer->method( 'compute' )->willReturnCallback(
			static function ( $var, $vars, $cb ) use ( $alreadySetName ) {
				return $cb( $alreadySetName );
			}
		);
		$name = 'second-var';
		$manager = $this->getManager( $computer );
		$lazyVar = new LazyLoadedVariable( '', [] );
		$setVars->setVar( $name, $lazyVar );
		yield 'set, lazy-loaded with callback' => [ $manager, $setVars, $name, 0, $firstValue ];

		$name = 'afpd';
		$afpd = new AFPData( AFPData::DINT, 42 );
		$vars->setVar( $name, $afpd );
		yield 'set, AFPData' => [ $this->getManager(), $vars, $name, 0, $afpd ];

		$name = 'not-set';
		$expected = new AFPData( AFPData::DUNDEFINED );
		yield 'unset, lax' => [ $this->getManager(), $vars, $name, VariablesManager::GET_LAX, $expected ];
		yield 'unset, strict' => [
			$this->getManager(),
			$vars,
			$name,
			VariablesManager::GET_STRICT,
			UnsetVariableException::class
		];
		yield 'unset, bc' => [
			$this->getManager(),
			$vars,
			$name,
			VariablesManager::GET_BC,
			new AFPData( AFPData::DNULL )
		];
	}

	/**
	 * @covers ::exportAllVars
	 */
	public function testExportAllVars() {
		$pairs = [
			'foo' => 42,
			'bar' => [ 'bar', 'baz' ],
			'var' => false,
			'boo' => null
		];
		$vars = VariableHolder::newFromArray( $pairs );
		$manager = $this->getManager();

		$this->assertSame( $pairs, $manager->exportAllVars( $vars ) );
	}

	/**
	 * @covers ::exportNonLazyVars
	 */
	public function testExportNonLazyVars() {
		$afcv = $this->createMock( LazyLoadedVariable::class );
		$pairs = [
			'lazy1' => $afcv,
			'lazy2' => $afcv,
			'native1' => 42,
			'native2' => 'foo',
			'native3' => null,
			'afpd' => new AFPData( AFPData::DSTRING, 'hey' ),
		];
		$vars = VariableHolder::newFromArray( $pairs );
		$manager = $this->getManager();

		$nonLazy = [
			'native1' => '42',
			'native2' => 'foo',
			'native3' => '',
			'afpd' => 'hey'
		];

		$this->assertSame( $nonLazy, $manager->exportNonLazyVars( $vars ) );
	}

	/**
	 * @covers ::__construct
	 */
	public function testConstruct() {
		$this->assertInstanceOf(
			VariablesManager::class,
			new VariablesManager(
				$this->createMock( KeywordsManager::class ),
				$this->createMock( LazyVariableComputer::class )
			)
		);
	}
}

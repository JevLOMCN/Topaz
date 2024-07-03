<?php

namespace MediaWiki\Extension\AbuseFilter\Tests\Unit;

use MediaWiki\Extension\AbuseFilter\KeywordsManager;
use MediaWiki\Extension\AbuseFilter\Variables\VariableHolder;
use MediaWiki\Extension\AbuseFilter\Variables\VariablesFormatter;
use MediaWiki\Extension\AbuseFilter\Variables\VariablesManager;
use MediaWikiUnitTestCase;
use MessageLocalizer;
use Wikimedia\TestingAccessWrapper;

/**
 * @coversDefaultClass \MediaWiki\Extension\AbuseFilter\Variables\VariablesFormatter
 */
class VariablesFormatterTest extends MediaWikiUnitTestCase {
	/**
	 * @covers ::__construct
	 */
	public function testConstruct() {
		$this->assertInstanceOf(
			VariablesFormatter::class,
			new VariablesFormatter(
				$this->createMock( KeywordsManager::class ),
				$this->createMock( VariablesManager::class ),
				$this->createMock( MessageLocalizer::class )
			)
		);
	}

	/**
	 * @covers ::setMessageLocalizer
	 */
	public function testSetMessageLocalizer() {
		$formatter = new VariablesFormatter(
			$this->createMock( KeywordsManager::class ),
			$this->createMock( VariablesManager::class ),
			$this->createMock( MessageLocalizer::class )
		);
		$ml = $this->createMock( MessageLocalizer::class );
		$formatter->setMessageLocalizer( $ml );
		/** @var VariablesFormatter $wrapper */
		$wrapper = TestingAccessWrapper::newFromObject( $formatter );
		$this->assertSame( $ml, $wrapper->messageLocalizer );
	}

	/**
	 * @param mixed $var
	 * @param string $expected
	 * @covers ::formatVar
	 * @dataProvider provideFormatVar
	 */
	public function testFormatVar( $var, string $expected ) {
		$this->assertSame( $expected, VariablesFormatter::formatVar( $var ) );
	}

	/**
	 * Provider for testFormatVar
	 * @return array
	 */
	public static function provideFormatVar() {
		return [
			'boolean' => [ true, 'true' ],
			'single-quote string' => [ 'foo', "'foo'" ],
			'string with quotes' => [ "ba'r'", "'ba'r''" ],
			'integer' => [ 42, '42' ],
			'float' => [ 0.1, '0.1' ],
			'null' => [ null, 'null' ],
			'simple list' => [ [ true, 1, 'foo' ], "[\n\t0 => true,\n\t1 => 1,\n\t2 => 'foo'\n]" ],
			'assoc array' => [ [ 'foo' => 1, 'bar' => 'bar' ], "[\n\t'foo' => 1,\n\t'bar' => 'bar'\n]" ],
			'nested array' => [
				[ 'a1' => 1, [ 'a2' => 2, [ 'a3' => 3, [ 'a4' => 4 ] ] ] ],
				"[\n\t'a1' => 1,\n\t0 => [\n\t\t'a2' => 2,\n\t\t0 => [\n\t\t\t'a3' => 3,\n\t\t\t0 => " .
				"[\n\t\t\t\t'a4' => 4\n\t\t\t]\n\t\t]\n\t]\n]"
			],
			'empty array' => [ [], '[]' ],
			'mixed array' => [
				[ 3 => true, 'foo' => false, 1, [ 1, 'foo' => 42 ] ],
				"[\n\t3 => true,\n\t'foo' => false,\n\t4 => 1,\n\t5 => [\n\t\t0 => 1,\n\t\t'foo' => 42\n\t]\n]"
			]
		];
	}

	/**
	 * @covers ::buildVarDumpTable
	 */
	public function testBuildVarDumpTable_empty() {
		$ml = $this->createMock( MessageLocalizer::class );
		$ml->method( 'msg' )->willReturnCallback( function ( $key ) {
			return $this->getMockMessage( $key );
		} );
		$formatter = new VariablesFormatter(
			$this->createMock( KeywordsManager::class ),
			$this->createMock( VariablesManager::class ),
			$ml
		);

		$actual = $formatter->buildVarDumpTable( new VariableHolder() );
		$this->assertStringContainsString( 'abusefilter-log-details-var', $actual, 'header' );
		$this->assertStringNotContainsString( 'mw-abuselog-var-value', $actual, 'no values' );
	}

	/**
	 * @covers ::buildVarDumpTable
	 */
	public function testBuildVarDumpTable() {
		$ml = $this->createMock( MessageLocalizer::class );
		$ml->method( 'msg' )->willReturnCallback( function ( $key ) {
			return $this->getMockMessage( $key );
		} );
		$kManager = $this->createMock( KeywordsManager::class );
		$varMessage = 'Dummy variable message';
		$varArray = [ 'foo' => true, 'bar' => 'foobar' ];
		$kManager->expects( $this->atLeastOnce() )
			->method( 'getMessageKeyForVar' )
			->willReturnCallback( static function ( $var ) use ( $varMessage ) {
				return $var === 'foo' ? $varMessage : null;
			} );
		$holder = VariableHolder::newFromArray( $varArray );
		$varManager = $this->createMock( VariablesManager::class );
		$varManager->method( 'exportAllVars' )->willReturn( $varArray );
		$formatter = new VariablesFormatter( $kManager, $varManager, $ml );

		$actual = $formatter->buildVarDumpTable( $holder );
		$this->assertStringContainsString( 'abusefilter-log-details-var', $actual, 'header' );
		$this->assertStringContainsString( 'mw-abuselog-var-value', $actual, 'values' );
		$this->assertStringContainsString( '<code', $actual, 'formatted var name' );
		$this->assertSame( 1, substr_count( $actual, $varMessage ), 'only one var with message' );
	}
}

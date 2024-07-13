<?php

use DataValues\NumberValue;
use DataValues\StringValue;
use MediaWiki\Extension\Math\MathFormatter;
use MediaWiki\Extension\Math\Tests\MathMockHttpTrait;
use Wikibase\Lib\Formatters\SnakFormatter;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\LBFactory;

/**
 * Test the results of MathFormatter
 *
 * @covers \MediaWiki\Extension\Math\MathFormatter
 *
 * @group Math
 *
 * @license GPL-2.0-or-later
 */
class MathFormatterTest extends MediaWikiIntegrationTestCase {
	use MathMockHttpTrait;

	private const SOME_TEX = '\sin x^2';

	protected function setUp(): void {
		$this->markTestSkippedIfExtensionNotLoaded( 'WikibaseClient' );
		$db = $this->createMock( IDatabase::class );
		$db->method( 'selectRow' )->willReturn( false );
		$lbFactory = $this->createMock( LBFactory::class );
		$lbFactory->method( 'getReplicaDatabase' )->willReturn( $db );
		$this->setService( 'DBLoadBalancerFactory', $lbFactory );
		parent::setUp();
	}

	/**
	 * Checks the
	 * @covers \MediaWiki\Extension\Math\MathFormatter::__construct
	 */
	public function testBasics() {
		$formatter = new MathFormatter( SnakFormatter::FORMAT_PLAIN );
		// check if the format input was corretly passed to the class
		$this->assertSame( SnakFormatter::FORMAT_PLAIN, $formatter->getFormat(), 'test getFormat' );
	}

	public function testNotStringValue() {
		$formatter = new MathFormatter( SnakFormatter::FORMAT_PLAIN );
		$this->expectException( InvalidArgumentException::class );
		$formatter->format( new NumberValue( 0 ) );
	}

	public function testNullValue() {
		$formatter = new MathFormatter( SnakFormatter::FORMAT_PLAIN );
		$this->expectException( InvalidArgumentException::class );
		$formatter->format( null );
	}

	public function testUnknownFormatFallsBackToMathMl() {
		$this->setupGoodMathRestBaseMockHttp( true );

		$formatter = new MathFormatter( 'unknown/unknown' );
		$value = new StringValue( self::SOME_TEX );
		$resultFormat = $formatter->format( $value );
		$this->assertStringContainsString( '</math>', $resultFormat );
	}

	/**
	 * @covers \MediaWiki\Extension\Math\MathFormatter::format
	 */
	public function testUnknownFormatFailure() {
		$this->setupBadMathRestBaseMockHttp();

		$formatter = new MathFormatter( 'unknown/unknown' );
		$value = new StringValue( '\newcommand' );
		$resultFormat = $formatter->format( $value );
		$this->assertStringContainsString( 'unknown function', $resultFormat );
	}

	public function testFormatPlain() {
		$formatter = new MathFormatter( SnakFormatter::FORMAT_PLAIN );
		$value = new StringValue( self::SOME_TEX );
		$resultFormat = $formatter->format( $value );
		$this->assertSame( self::SOME_TEX, $resultFormat );
	}

	public function testFormatHtml() {
		$this->setupGoodMathRestBaseMockHttp( true );

		$formatter = new MathFormatter( SnakFormatter::FORMAT_HTML );
		$value = new StringValue( self::SOME_TEX );
		$resultFormat = $formatter->format( $value );
		$this->assertStringContainsString( '</math>', $resultFormat, 'Result must contain math-tag' );
	}

	public function testFormatDiffHtml() {
		$this->setupGoodMathRestBaseMockHttp( true );

		$formatter = new MathFormatter( SnakFormatter::FORMAT_HTML_DIFF );
		$value = new StringValue( self::SOME_TEX );
		$resultFormat = $formatter->format( $value );
		$this->assertStringContainsString( '</math>', $resultFormat, 'Result must contain math-tag' );
		$this->assertStringContainsString( '</h4>', $resultFormat, 'Result must contain a <h4> tag' );
		$this->assertStringContainsString( '</code>', $resultFormat, 'Result must contain a <code> tag' );
		$this->assertStringContainsString(
			'wb-details',
			$resultFormat,
			'Result must contain wb-details class'
		);
		$this->assertStringContainsString(
			htmlspecialchars( self::SOME_TEX ),
			$resultFormat,
			'Result must contain the TeX source'
		);
	}

	public function testFormatXWiki() {
		$tex = self::SOME_TEX;
		$formatter = new MathFormatter( SnakFormatter::FORMAT_WIKI );
		$value = new StringValue( self::SOME_TEX );
		$resultFormat = $formatter->format( $value );
		$this->assertSame( "<math>$tex</math>", $resultFormat, 'Tex wasn\'t properly wrapped' );
	}

}

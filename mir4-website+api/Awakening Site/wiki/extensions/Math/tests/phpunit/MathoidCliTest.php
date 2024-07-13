<?php

use MediaWiki\Extension\Math\MathMathMLCli;

/**
 * @covers \MediaWiki\Extension\Math\MathMathMLCli
 *
 * @group Math
 *
 * @license GPL-2.0-or-later
 */
class MathoidCliTest extends MediaWikiIntegrationTestCase {
	private $goodInput = '\sin\left(\frac12x\right)';
	private $badInput = '\newcommand{\text{do evil things}}';
	protected static $hasMathoidCli;

	public static function setUpBeforeClass(): void {
		global $wgMathoidCli;
		if ( is_array( $wgMathoidCli ) && is_executable( $wgMathoidCli[0] ) ) {
			self::$hasMathoidCli = true;
		}
	}

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp(): void {
		parent::setUp();
		if ( !self::$hasMathoidCli ) {
			$this->markTestSkipped( "No mathoid cli configured on server" );
		}
	}

	public function testGood() {
		$mml = new MathMathMLCli( $this->goodInput );
		$input = [ 'good' => $mml ];
		MathMathMLCli::batchEvaluate( $input );
		$this->assertTrue( $mml->render(), 'assert that renders' );
		$this->assertStringContainsString( '</mo>', $mml->getMathml() );
	}

	public function testUndefinedFunctionError() {
		$mml = new MathMathMLCli( $this->badInput );
		$input = [ 'bad' => $mml ];
		MathMathMLCli::batchEvaluate( $input );
		$this->assertFalse( $mml->render(), 'assert that fails' );
		$this->assertStringContainsString( 'newcommand', $mml->getLastError() );
	}

	public function testSyntaxError() {
		$mml = new MathMathMLCli( '^' );
		$input = [ 'bad' => $mml ];
		MathMathMLCli::batchEvaluate( $input );
		$this->assertFalse( $mml->render(), 'assert that fails' );
		$this->assertStringContainsString( 'SyntaxError', $mml->getLastError() );
	}

	public function testCeError() {
		$mml = new MathMathMLCli( '\ce{H2O}' );
		$input = [ 'bad' => $mml ];
		MathMathMLCli::batchEvaluate( $input );
		$this->assertFalse( $mml->render(), 'assert that fails' );
		$this->assertStringContainsString( 'SyntaxError', $mml->getLastError() );
	}

	public function testEmpty() {
		$mml = new MathMathMLCli( '' );
		$input = [ 'bad' => $mml ];
		MathMathMLCli::batchEvaluate( $input );
		$this->assertFalse( $mml->render(), 'assert that renders' );
		$this->assertFalse( $mml->isTexSecure() );
		$this->assertStringContainsString( 'empty', $mml->getLastError() );
	}

}

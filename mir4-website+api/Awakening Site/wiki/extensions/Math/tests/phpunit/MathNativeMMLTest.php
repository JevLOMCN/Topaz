<?php

use MediaWiki\Extension\Math\MathNativeMML;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\LBFactory;

/**
 * Test the native MathML output format.
 *
 * @covers \MediaWiki\Extension\Math\MathNativeMML
 *
 * @group Math
 *
 * @license GPL-2.0-or-later
 */
class MathNativeMMLTest extends MediaWikiIntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();
		$db = $this->createMock( IDatabase::class );
		$db->method( 'selectRow' )->willReturn( false );
		$lbFactory = $this->createMock( LBFactory::class );
		$lbFactory->method( 'getReplicaDatabase' )->willReturn( $db );
		$this->setService( 'DBLoadBalancerFactory', $lbFactory );
		$this->setMwGlobals( 'wgMathValidModes', [ 'native' ] );
		$this->clearHooks();
	}

	public function testSin() {
		$mml = new MathNativeMML( '\sin' );
		$this->assertSame( 'tex', $mml->getInputType() );
		$this->assertTrue( $mml->checkTeX() );
		$this->assertTrue( $mml->render() );
		$this->assertStringContainsString( 'sin', $mml->getMathml() );
	}

	public function testNoLink() {
		$this->setMwGlobals( 'wgMathEnableFormulaLinks', false );
		$mml = new MathNativeMML( '\sin', [ 'qid' => 'Q1' ] );
		$this->assertTrue( $mml->render() );
		$this->assertStringNotContainsString( 'href', $mml->getMathml() );
	}

	public function testLink() {
		$this->setMwGlobals( 'wgMathEnableFormulaLinks', true );
		$mml = new MathNativeMML( '\sin', [ 'qid' => 'Q1' ] );
		$this->assertTrue( $mml->render() );
		$this->assertStringContainsString( 'href', $mml->getMathml() );
	}

	public function testId() {
		$mml = new MathNativeMML( '\sin', [ 'id' => 'unique-id' ] );
		$this->assertTrue( $mml->render() );
		$this->assertStringContainsString( 'unique-id', $mml->getMathml() );
	}

	public function testBlock() {
		$mml = new MathNativeMML( '\sin', [ 'display' => 'block' ] );
		$this->assertTrue( $mml->render() );
		$this->assertStringContainsString( 'block', $mml->getMathml() );
	}
}

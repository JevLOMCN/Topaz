<?php

use MediaWiki\Extension\Math\MathMathML;
use MediaWiki\Extension\Math\MathRenderer;

/**
 * Test the database access and core functionality of MathRenderer.
 *
 * @covers \MediaWiki\Extension\Math\MathRenderer
 *
 * @group Math
 *
 * @license GPL-2.0-or-later
 */
class MathCacheTest extends MediaWikiIntegrationTestCase {
	/**
	 * @var MathRenderer
	 */
	private $renderer;
	private const SOME_TEX = "a+b";
	private const SOME_MATHML = "iℏ∂_tΨ=H^Ψ<mrow><\ci>";

	protected function setUp(): void {
		parent::setUp();
		$this->renderer = new MathMathML( self::SOME_TEX );
	}

	/**
	 * Checks the tex and hash functions
	 * @covers \MediaWiki\Extension\Math\MathRenderer::getInputHash
	 */
	public function testInputHash() {
		$this->assertEquals( 'beb7506b16f7c36aa0f9c8c9ef41b40b', $this->renderer->getInputHash() );
	}

	/**
	 * Helper function to set the current state of the sample renderer instance to the test values
	 */
	public function setValues() {
		// set some values
		$this->renderer->setTex( self::SOME_TEX );
		$this->renderer->setMathml( self::SOME_MATHML );
	}

	/**
	 * Checks database access. Writes an entry and reads it back.
	 * @covers \MediaWiki\Extension\Math\MathRenderer::writeToCache
	 * @covers \MediaWiki\Extension\Math\MathRenderer::readFromCache
	 */
	public function testDBBasics() {
		$this->setValues();
		$this->renderer->writeToCache();
		$renderer2 = new MathMathML( self::SOME_TEX, [ 'display' => '' ] );
		$this->assertTrue( $renderer2->readFromCache(), 'Reading from database failed' );
		// comparing the class object does now work due to null values etc.
		$this->assertEquals(
			$this->renderer->getTex(), $renderer2->getTex(), "test if tex is the same"
		);
		$this->assertEquals(
			$this->renderer->getMathml(), $renderer2->getMathml(), "Check MathML encoding"
		);
		$this->assertEquals(
			$this->renderer->getHtmlOutput(), $renderer2->getHtmlOutput(), 'test if HTML is the same'
		);
	}

	/**
	 * This test checks if no additional write operation
	 * is performed, if the entry already existed in the database.
	 */
	public function testNoWrite() {
		$this->setValues();
		$inputHash = $this->renderer->getInputHash();
		$this->assertTrue( $this->renderer->isChanged() );
		$this->assertTrue( $this->renderer->writeCache(), "Write new entry" );
		$this->assertTrue( $this->renderer->readFromCache(), "Read entry from database" );
		$this->assertFalse( $this->renderer->isChanged() );
	}
}

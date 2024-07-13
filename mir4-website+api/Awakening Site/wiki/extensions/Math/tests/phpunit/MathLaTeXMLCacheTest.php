<?php

use MediaWiki\Extension\Math\MathLaTeXML;

/**
 * @covers \MediaWiki\Extension\Math\MathLaTeXML
 *
 * @group Math
 *
 * @license GPL-2.0-or-later
 */
class MathLaTeXMLCacheTest extends MediaWikiIntegrationTestCase {
	public $renderer;
	private const SOME_TEX = "a+b";
	private const SOME_MATHML = "iℏ∂_tΨ=H^Ψ<mrow><\ci>";

	/**
	 * Helper function to test protected/private Methods
	 * @param string $name
	 * @return ReflectionMethod
	 */
	protected static function getMethod( $name ) {
		$class = new ReflectionClass( MathLaTeXML::class );
		$method = $class->getMethod( $name );
		$method->setAccessible( true );
		return $method;
	}

	protected function setUp(): void {
		parent::setUp();
		$this->renderer = new MathLaTeXML( self::SOME_TEX );
	}

	/**
	 * Checks the tex and hash functions
	 * @covers \MediaWiki\Extension\Math\MathRenderer::getInputHash
	 */
	public function testInputHash() {
		$this->assertIsString( $this->renderer->getInputHash() );
		$this->assertStringMatchesFormat( '%x', $this->renderer->getInputHash() );
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
	 * @covers \MediaWiki\Extension\Math\MathLaTeXML::getMathTableName
	 */
	public function testTableName() {
		$fnGetMathTableName = self::getMethod( 'getMathTableName' );
		$obj = new MathLaTeXML();
		$tableName = $fnGetMathTableName->invokeArgs( $obj, [] );
		$this->assertEquals( "mathlatexml", $tableName, "Wrong latexml table name" );
	}

	/**
	 * Checks database access. Writes an entry and reads it back.
	 * @covers \MediaWiki\Extension\Math\MathRenderer::writeToCache
	 * @covers \MediaWiki\Extension\Math\MathRenderer::readFromCache
	 */
	public function testDBBasics() {
		$this->setValues();
		$this->renderer->writeToCache();

		$renderer2 = $this->renderer = new MathLaTeXML( self::SOME_TEX );
		$renderer2->readFromCache();
		// comparing the class object does now work due to null values etc.
		$this->assertEquals(
			$this->renderer->getTex(), $renderer2->getTex(), "test if tex is the same"
		);
		$this->assertEquals(
			$this->renderer->getMathml(), $renderer2->getMathml(), "Check MathML encoding"
		);
	}
}

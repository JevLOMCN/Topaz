<?php

use DataValues\StringValue;
use MediaWiki\Extension\Math\MathMLRdfBuilder;
use MediaWiki\Extension\Math\Tests\MathMockHttpTrait;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikimedia\Purtle\NTriplesRdfWriter;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\LBFactory;

/**
 * Test the MathML RDF formatter
 *
 * @group Math
 * @covers \MediaWiki\Extension\Math\MathMLRdfBuilder
 * @author Moritz Schubotz (physikerwelt)
 */
class MathMLRdfBuilderTest extends MediaWikiIntegrationTestCase {
	use MathMockHttpTrait;

	private const ACME_PREFIX_URL = 'http://acme/';
	private const ACME_REF = 'testing';

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
	 * @param string $test
	 * @return string
	 */
	private function makeCase( $test ) {
		$builder = new MathMLRdfBuilder();
		$writer = new NTriplesRdfWriter();
		$writer->prefix( 'www', "http://www/" );
		$writer->prefix( 'acme', self::ACME_PREFIX_URL );

		$writer->start();
		$writer->about( 'www', 'Q1' );

		$snak = new PropertyValueSnak( new NumericPropertyId( 'P1' ), new StringValue( $test ) );
		$builder->addValue( $writer, 'acme', self::ACME_REF, 'DUMMY', '', $snak );

		return trim( $writer->drain() );
	}

	public function testValidInput() {
		$this->setupGoodMathRestBaseMockHttp();

		$triples = $this->makeCase( '\sin x^2' );
		$this->assertStringContainsString(
			self::ACME_PREFIX_URL . self::ACME_REF . '> "<math',
			$triples
		);
		$this->assertStringContainsString( '<mi>sin</mi>\n', $triples );
		$this->assertStringContainsString( '<mn>2</mn>\n', $triples );
		$this->assertStringContainsString( 'x^{2}', $triples );
		$this->assertStringContainsString( '^^<http://www.w3.org/1998/Math/MathML> .', $triples );
	}

	public function testInvalidInput() {
		$this->setupBadMathRestBaseMockHttp();

		$triples = $this->makeCase( '\sin\newcommand' );
		$this->assertStringContainsString( '<math', $triples );
		$this->assertStringContainsString( 'unknown function', $triples );
		$this->assertStringContainsString( 'newcommand', $triples );
		$this->assertStringContainsString( '^^<http://www.w3.org/1998/Math/MathML> .', $triples );
	}
}

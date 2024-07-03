<?php

use MediaWiki\Extension\Math\MathDataUpdater;
use MediaWiki\Parser\ParserOutput;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Entity\PropertyDataTypeMatcher;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;

/**
 * Test the MathDataUpdater for Wikidata
 *
 * @covers \MediaWiki\Extension\Math\MathDataUpdater
 *
 * @license GPL-2.0-or-later
 */
class MathDataUpdaterTest extends MediaWikiIntegrationTestCase {

	/**
	 * @var NumericPropertyId
	 */
	private $mathProperty;
	/**
	 * @var NumericPropertyId
	 */
	private $otherProperty;

	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->markTestSkippedIfExtensionNotLoaded( 'WikibaseClient' );
		$this->mathProperty = new NumericPropertyId( 'P' . DummyPropertyDataTypeLookup::$mathId );
		$this->otherProperty = new NumericPropertyId( 'P' . ( DummyPropertyDataTypeLookup::$mathId + 1 ) );
	}

	public function testNoMath() {
		$matcher = new PropertyDataTypeMatcher( new DummyPropertyDataTypeLookup() );
		$updater = new MathDataUpdater( $matcher );
		$statement = new Statement( new PropertyNoValueSnak( $this->otherProperty ) );
		$updater->processStatement( $statement );
		$parserOutput = $this->getMockBuilder( ParserOutput::class )->onlyMethods( [
			'addModules',
			'addModuleStyles',
		] )->getMock();
		$parserOutput->expects( $this->never() )->method( 'addModuleStyles' );
		/** @var ParserOutput $parserOutput */
		$updater->updateParserOutput( $parserOutput );
	}

	public function testMath() {
		$matcher = new PropertyDataTypeMatcher( new DummyPropertyDataTypeLookup() );
		$updater = new MathDataUpdater( $matcher );
		$statement = new Statement( new PropertyNoValueSnak( $this->mathProperty ) );
		$updater->processStatement( $statement );
		$parserOutput = $this->getMockBuilder( ParserOutput::class )->onlyMethods( [
			'addModules',
			'addModuleStyles',
		] )->getMock();
		$parserOutput->expects( $this->once() )->method( 'addModuleStyles' );
		/** @var ParserOutput $parserOutput */
		$updater->updateParserOutput( $parserOutput );
	}
}

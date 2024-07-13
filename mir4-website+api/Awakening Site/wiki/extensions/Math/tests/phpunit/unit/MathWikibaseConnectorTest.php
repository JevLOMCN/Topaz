<?php

namespace MediaWiki\Extension\Math\Tests;

use DataValues\StringValue;
use MediaWiki\Config\ConfigException;
use MediaWiki\Languages\LanguageNameUtils;
use MediaWiki\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\StorageException;

/**
 * @covers \MediaWiki\Extension\Math\MathWikibaseConnector
 */
class MathWikibaseConnectorTest extends MathWikibaseConnectorTestFactory {

	public function testGetUrl() {
		$mathWikibase = $this->getWikibaseConnector();
		$this->assertEquals( self::EXAMPLE_URL . 'wiki/Special:EntityPage/Q42',
			$mathWikibase->buildURL( 'Q42' ) );
	}

	public function testFetchInvalidLanguage() {
		$languageNameUtils = $this->createMock( LanguageNameUtils::class );
		$languageNameUtils->method( 'isValidCode' )
			->willReturn( false );
		$mathWikibase = $this->getWikibaseConnector( null, $languageNameUtils );

		$this->expectException( 'InvalidArgumentException' );
		$this->expectExceptionMessage( 'Invalid language code specified.' );
		$mathWikibase->fetchWikibaseFromId( 'Q1', '&' );
	}

	public function testFetchWithStorageIssue() {
		$entityRevisionLookup = $this->createMock( EntityRevisionLookup::class );
		$entityRevisionLookup->method( 'getEntityRevision' )
			->willThrowException( new StorageException( 'Invalid code' ) );
		$mathWikibase = $this->getWikibaseConnector( null, null, null, $entityRevisionLookup );

		$this->expectException( 'InvalidArgumentException' );
		$this->expectExceptionMessage( 'Non-existing Wikibase ID.' );
		$mathWikibase->fetchWikibaseFromId( 'Q1', '&' );
	}

	public function testFetchNonExistingId() {
		$mathWikibase = $this->getWikibaseConnector();
		$this->expectException( 'InvalidArgumentException' );
		$this->expectExceptionMessage( 'Non-existing Wikibase ID.' );
		$mathWikibase->fetchWikibaseFromId( 'Q1', 'en' );
	}

	public function testFailSafeFaultyPropertySetup() {
		$dummyItemId = new ItemId( 'Q1' );
		$parserMock = $this->createMock( BasicEntityIdParser::class );
		$parserMock->method( 'parse' )
			->willReturnCallback(
				static function ( string $id ) {
					if ( $id === 'Q1' ) {
						return new ItemId( 'Q1' );
					} else {
						throw new ConfigException();
					}
				} );

		$revisionLookupMock = $this->createMock( EntityRevisionLookup::class );
		$revisionLookupMock->expects( $this->once() )
			->method( 'getEntityRevision' )
			->with( $dummyItemId )
			->willReturn( null );

		// non-existing properties should not result in errors on initialization
		$mathWikibase = $this->getWikibaseConnector(
			null,
			null,
			null,
			$revisionLookupMock,
			null,
			$parserMock
		);

		// but obviously on non-existing errors when trying to fetch information
		$this->expectException( 'InvalidArgumentException' );
		$this->expectExceptionMessage( 'Non-existing Wikibase ID.' );
		$mathWikibase->fetchWikibaseFromId( 'Q1', 'en' );
	}

	public function testFetchMalformedId() {
		$parserMock = $this->createMock( BasicEntityIdParser::class );
		$parserMock->method( 'parse' )
			->willReturnCallback(
				static function ( string $id ) {
					if ( $id === '1' ) {
						throw new EntityIdParsingException();
					} else {
						return null;
					}
				} );

		$mathWikibase = $this->getWikibaseConnector( null, null, null, null, null, $parserMock );
		$this->expectException( 'InvalidArgumentException' );
		$this->expectExceptionMessage( 'Invalid Wikibase ID.' );
		$mathWikibase->fetchWikibaseFromId( '1', 'en' );
	}

	public function testFetchNonItem() {
		// a mocked Item does not pass instanceof, hence the InvalidArgumentException
		$entityRevisionMock = $this->createMock( EntityRevision::class );
		$wikibaseConnector = $this->getWikibaseConnectorWithExistingItems( $entityRevisionMock );
		$this->expectException( 'InvalidArgumentException' );
		$this->expectExceptionMessage( 'The specified Wikibase ID does not represented an item.' );
		$wikibaseConnector->fetchWikibaseFromId( 'Q1', 'en' );
	}

	public function testFetchEmptyItem() {
		$itemId = new ItemId( 'Q1' );
		$item = new Item( $itemId );
		$revision = new EntityRevision( $item );

		$parserMock = $this->createMock( BasicEntityIdParser::class );
		$parserMock->method( 'parse' )
			->willReturnCallback(
				static function ( string $id ) {
					if ( str_starts_with( $id, 'Q' ) ) {
						return new ItemId( $id );
					} else {
						throw new ConfigException();
					}
				} );

		$wikibaseConnector = $this->getWikibaseConnectorWithExistingItems(
			$revision,
			false,
			null,
			$parserMock
		);
		$wikibaseInfo = $wikibaseConnector->fetchWikibaseFromId( 'Q1', 'en' );
		$this->assertEquals( $itemId, $wikibaseInfo->getId() );
		$this->assertEquals( self::TEST_ITEMS[ 'Q1' ][0], $wikibaseInfo->getLabel() );
		$this->assertEquals( self::TEST_ITEMS[ 'Q1' ][1], $wikibaseInfo->getDescription() );
		$this->assertCount( 0, $wikibaseInfo->getParts() );
		$this->assertFalse( $wikibaseInfo->hasParts() );
		$this->assertNull( $wikibaseInfo->getSymbol() );
		$this->assertNull( $wikibaseInfo->getFormattedSymbol() );
	}

	public function testFetchItemWithFormula() {
		$itemId = new ItemId( 'Q1' );
		$item = new Item( $itemId );
		$revision = new EntityRevision( $item );

		$formulaValue = new StringValue( self::TEST_ITEMS[ 'Q1' ][2] );
		$definingFormulaStatement = new Statement( new PropertyValueSnak(
			new NumericPropertyId( 'P2' ),
			$formulaValue
		) );

		$item->setStatements( new StatementList( $definingFormulaStatement ) );

		$wikibaseConnector = $this->getWikibaseConnectorWithExistingItems( $revision );
		$wikibaseInfo = $wikibaseConnector->fetchWikibaseFromId( 'Q1', 'en' );
		$this->assertFalse( $wikibaseInfo->hasParts() );
		$this->assertEquals( $formulaValue, $wikibaseInfo->getSymbol() );
		$this->assertEquals(
			$this->getExpectedMathML( $formulaValue->getValue() ),
			$wikibaseInfo->getFormattedSymbol()
		);
	}

	/**
	 * @dataProvider provideItemSetups
	 */
	public function testFetchMassEnergyEquivalenceHasPartsItem( bool $hasPart ) {
		$item = $this->setupMassEnergyEquivalenceItem( $hasPart );
		$wikibaseConnector = $this->getWikibaseConnectorWithExistingItems( new EntityRevision( $item ) );
		$wikibaseInfo = $wikibaseConnector->fetchWikibaseFromId( 'Q1', 'en' );

		$this->assertEquals( $item->getId(), $wikibaseInfo->getId() );
		$this->assertEquals( self::TEST_ITEMS[ 'Q1' ][0], $wikibaseInfo->getLabel() );
		$this->assertEquals( self::TEST_ITEMS[ 'Q1' ][1], $wikibaseInfo->getDescription() );
		$mathFormula = self::TEST_ITEMS[ 'Q1' ][2];
		$this->assertEquals( $mathFormula, $wikibaseInfo->getSymbol()->getValue() );
		$this->assertEquals( $this->getExpectedMathML( $mathFormula ), $wikibaseInfo->getFormattedSymbol() );

		$this->assertTrue( $wikibaseInfo->hasParts() );
		$parts = $wikibaseInfo->getParts();
		$this->assertCount( 3, $parts );
		foreach ( $parts as $part ) {
			$key = $part->getId()->getSerialization();
			$this->assertEquals( self::TEST_ITEMS[ $key ][0], $part->getLabel() );
			$this->assertEquals( self::TEST_ITEMS[ $key ][1], $part->getDescription() );
			$mathFormula = self::TEST_ITEMS[ $key ][2];
			$this->assertEquals( $mathFormula, $part->getSymbol()->getValue() );
			$this->assertEquals( $this->getExpectedMathML( $mathFormula ), $part->getFormattedSymbol() );
			$this->assertEquals( self::EXAMPLE_URL, $part->getUrl() );
		}
	}

	/**
	 * @dataProvider provideItemSetups
	 */
	public function testFetchMassEnergyWithStorageExceptionLogging( bool $hasPart ) {
		$logger = $this->createMock( LoggerInterface::class );
		$logger->expects( $this->once() )
			->method( 'warning' )
			->with( 'Cannot fetch URL for EntityId Q3. Reason: Test Exception' );

		$item = $this->setupMassEnergyEquivalenceItem( $hasPart );
		$wikibaseConnector = $this->getWikibaseConnectorWithExistingItems(
			new EntityRevision( $item ),
			true,
			$logger
		);

		$wikibaseConnector->fetchWikibaseFromId( 'Q1', 'en' );
	}

	/**
	 * @dataProvider provideItemSetups
	 */
	public function testFetchMassEnergyWithStorageException( bool $hasPart ) {
		$item = $this->setupMassEnergyEquivalenceItem( $hasPart );
		$wikibaseConnector = $this->getWikibaseConnectorWithExistingItems(
			new EntityRevision( $item ),
			true,
			LoggerFactory::getInstance( 'Math' )
		);

		$wikibaseInfo = $wikibaseConnector->fetchWikibaseFromId( 'Q1', 'en' );
		$this->assertTrue( $wikibaseInfo->hasParts() );
		$parts = $wikibaseInfo->getParts();
		$this->assertCount( 3, $parts );
		foreach ( $parts as $part ) {
			$key = $part->getId()->getSerialization();
			if ( $key === 'Q3' ) {
				$this->assertNull( $part->getUrl() );
			} else {
				$this->assertEquals( self::EXAMPLE_URL, $part->getUrl() );
			}
		}
	}

	public static function provideItemSetups(): array {
		return [
			[ true ],
			[ false ],
		];
	}
}

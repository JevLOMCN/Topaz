<?php

namespace MediaWiki\Extension\Math\Tests;

use DataValues\StringValue;
use ExtensionRegistry;
use Language;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\Math\MathFormatter;
use MediaWiki\Extension\Math\MathWikibaseConnector;
use MediaWiki\Languages\LanguageFactory;
use MediaWiki\Languages\LanguageNameUtils;
use MediaWiki\Site\Site;
use MediaWikiUnitTestCase;
use Psr\Log\LoggerInterface;
use TestLogger;
use Wikibase\Client\RepoLinker;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookup;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookupFactory;
use Wikibase\Lib\Store\StorageException;

class MathWikibaseConnectorTestFactory extends MediaWikiUnitTestCase {
	public const EXAMPLE_URL = 'https://example.com/';

	public const TEST_ITEMS = [
			'Q1' => [ 'mass–energy equivalence', 'physical law relating mass to energy', 'E = mc^2' ],
			'Q2' => [ 'energy', 'measure for the ability of a system to do work', 'E' ],
			'Q3' => [
				'speed of light',
				'speed at which all massless particles and associated fields travel in vacuum',
				'c'
			],
			'Q4' => [
				'mass',
				'property of matter to resist changes of the state of motion and to attract other bodies',
				'm'
			]
		];

	public static function setUpBeforeClass(): void {
		ExtensionRegistry::enableForTest();
		if ( !ExtensionRegistry::getInstance()->isLoaded( 'WikibaseClient' ) ) {
			self::markTestSkipped( 'WikibaseClient is not installed. Skipping tests.' );
		}
		ExtensionRegistry::disableForTest();
		parent::setUpBeforeClass();
	}

	public function getWikibaseConnectorWithExistingItems(
		EntityRevision $entityRevision,
		bool $storageExceptionOnQ3 = false,
		LoggerInterface $logger = null,
		EntityIdParser $parser = null
	): MathWikibaseConnector {
		$revisionLookupMock = $this->createMock( EntityRevisionLookup::class );
		$revisionLookupMock->method( 'getEntityRevision' )->willReturnCallback(
			static function ( EntityId $entityId ) use ( $entityRevision, $storageExceptionOnQ3 ) {
				if ( $storageExceptionOnQ3 && $entityId->getSerialization() === 'Q3' ) {
					throw new StorageException( 'Test Exception' );
				} else {
					return $entityRevision;
				}
			}
		);
		$revisionLookupMock->expects( self::atLeastOnce() )
			->method( 'getEntityRevision' );

		$fallbackLabelDescriptionLookupFactoryMock = $this->createMock( FallbackLabelDescriptionLookupFactory::class );
		$languageMock = $this->createMock( Language::class );
		$languageFactoryMock = $this->createMock( LanguageFactory::class );
		$languageFactoryMock->method( 'getLanguage' )
			->with( 'en' )
			->willReturn( $languageMock );
		$languageNameUtilsMock = $this->createMock( LanguageNameUtils::class );
		$languageNameUtilsMock->method( 'isValidCode' )
			->with( 'en' )
			->willReturn( true );
		$fallbackLabelDescriptionLookupFactoryMock->method( 'newLabelDescriptionLookup' )
			->with( $languageMock )
			->willReturnCallback( [ $this, 'newLabelDescriptionLookup' ] );

		return self::getWikibaseConnector(
			$languageFactoryMock,
			$languageNameUtilsMock,
			$fallbackLabelDescriptionLookupFactoryMock,
			$revisionLookupMock,
			$logger,
			$parser
		);
	}

	public function getWikibaseConnector(
		LanguageFactory $languageFactory = null,
		LanguageNameUtils $languageNameUtils = null,
		FallbackLabelDescriptionLookupFactory $labelDescriptionLookupFactory = null,
		EntityRevisionLookup $entityRevisionLookupMock = null,
		LoggerInterface $logger = null,
		EntityIdParser $parser = null
	): MathWikibaseConnector {
		$labelDescriptionLookupFactory = $labelDescriptionLookupFactory ?:
			$this->createMock( FallbackLabelDescriptionLookupFactory::class );

		$entityRevisionLookup = $entityRevisionLookupMock ?:
			$this->createMock( EntityRevisionLookup::class );

		$languageFactory = $languageFactory ?: $this->createMock( LanguageFactory::class );
		if ( !$languageNameUtils ) {
			$languageNameUtils = $this->createMock( LanguageNameUtils::class );
			$languageNameUtils->method( 'isValidCode' )->willReturn( true );
		}

		$site = $this->createMock( Site::class );
		$site->method( 'getGlobalId' )->willReturn( '' );
		$site->method( 'getPageUrl' )->willReturn( self::EXAMPLE_URL );

		$repoConnector = $this->createMock( RepoLinker::class );
		$repoConnector->method( 'getEntityUrl' )
			->willReturnCallback( static function ( ItemId $itemId ) {
				return self::EXAMPLE_URL . 'wiki/Special:EntityPage/' . $itemId->getSerialization();
			} );

		$mathFormatter = $this->createMock( MathFormatter::class );
		$mathFormatter->method( 'format' )
			->willReturnCallback( static function ( StringValue $value ) {
				return self::getExpectedMathML( $value->getValue() );
			} );

		return new MathWikibaseConnector(
			new ServiceOptions( MathWikibaseConnector::CONSTRUCTOR_OPTIONS, [
				'MathWikibasePropertyIdHasPart' => 'P1',
				'MathWikibasePropertyIdDefiningFormula' => 'P2',
				'MathWikibasePropertyIdQuantitySymbol' => 'P3',
				'MathWikibasePropertyIdInDefiningFormula' => 'P4',
				'MathWikibasePropertyIdSymbolRepresents' => 'P5'
			] ),
			$repoConnector,
			$languageFactory,
			$languageNameUtils,
			$entityRevisionLookup,
			$labelDescriptionLookupFactory,
			$site,
			$parser ?: new BasicEntityIdParser(),
			$mathFormatter,
			$logger ?: new TestLogger()
		);
	}

	public function setupMassEnergyEquivalenceItem(
		bool $hasPartMode
	) {
		$partPropertyId = new NumericPropertyId( $hasPartMode ? 'P1' : 'P4' );
		$symbolPropertyId = new NumericPropertyId( $hasPartMode ? 'P3' : 'P5' );
		$items = [];
		$statements = [];
		foreach ( self::TEST_ITEMS as $key => $itemInfo ) {
			$itemId = new ItemId( $key );
			$items[ $key ] = new Item( $itemId );

			$siteLinkMock = $this->createMock( SiteLink::class );
			$siteLinkMock->method( 'getSiteId' )->willReturn( '' );
			$siteLinkMock->method( 'getPageName' )->willReturn( '' );
			$items[ $key ]->addSiteLink( $siteLinkMock );

			if ( $key === 'Q1' ) {
				continue;
			}

			$partSnak = new PropertyValueSnak(
				$partPropertyId,
				$hasPartMode ? new EntityIdValue( $items[ $key ]->getId() ) : new StringValue( $itemInfo[2] )
			);
			$partQualifier = new PropertyValueSnak(
				$symbolPropertyId,
				$hasPartMode ? new StringValue( $itemInfo[2] ) : new EntityIdValue( $items[ $key ]->getId() )
			);

			$statement = new Statement( $partSnak );
			$statement->setQualifiers( new SnakList( [ $partQualifier ] ) );
			$statements[] = $statement;
		}

		$mainFormulaValue = new StringValue( self::TEST_ITEMS[ 'Q1' ][2] );
		$definingFormulaStatement = new Statement( new PropertyValueSnak(
			new NumericPropertyId( 'P2' ),
			$mainFormulaValue
		) );

		$statementList = new StatementList( ...$statements );
		$statementList->addStatement( $definingFormulaStatement );
		$items[ 'Q1' ]->setStatements( $statementList );
		return $items[ 'Q1' ];
	}

	public function newLabelDescriptionLookup(): FallbackLabelDescriptionLookup {
		$lookup = $this->createMock( FallbackLabelDescriptionLookup::class );

		$lookup->method( 'getLabel' )
			->willReturnCallback( static function ( EntityId $entityId ) {
				if ( self::TEST_ITEMS[ $entityId->getSerialization() ] !== null ) {
					return new Term( 'en', self::TEST_ITEMS[ $entityId->getSerialization() ][0] );
				} else {
					return null;
				}
			} );

		$lookup->method( 'getDescription' )
			->willReturnCallback( static function ( EntityId $entityId ) {
				if ( self::TEST_ITEMS[ $entityId->getSerialization() ] !== null ) {
					return new Term( 'en', self::TEST_ITEMS[ $entityId->getSerialization() ][1] );
				} else {
					return null;
				}
			} );

		return $lookup;
	}

	public static function getExpectedMathML( $str ) {
		return '<math>' . $str . '</math>';
	}
}

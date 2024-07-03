<?php

namespace MediaWiki\Extension\Math;

use DataValues\StringValue;
use InvalidArgumentException;
use MediaWiki\Config\ConfigException;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Languages\LanguageFactory;
use MediaWiki\Languages\LanguageNameUtils;
use MediaWiki\Site\Site;
use Psr\Log\LoggerInterface;
use Wikibase\Client\RepoLinker;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookupFactory;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Lib\Store\StorageException;

/**
 * A class that connects with the local instance of wikibase to fetch
 * information from single items. There is always only one instance of this class.
 *
 * @see MathWikibaseConnector::getInstance()    to get an instance of the class
 */
class MathWikibaseConnector {
	/** @var string[] */
	public const CONSTRUCTOR_OPTIONS = [
		'MathWikibasePropertyIdHasPart',
		'MathWikibasePropertyIdDefiningFormula',
		'MathWikibasePropertyIdInDefiningFormula',
		'MathWikibasePropertyIdQuantitySymbol',
		'MathWikibasePropertyIdSymbolRepresents'
	];

	/** @var LoggerInterface */
	private $logger;

	/** @var RepoLinker */
	private $repoLinker;

	/** @var LanguageFactory */
	private $languageFactory;

	/** @var LanguageNameUtils */
	private $languageNameUtils;

	/** @var EntityRevisionLookup */
	private $entityRevisionLookup;

	/** @var Site */
	private $site;

	/** @var FallbackLabelDescriptionLookupFactory */
	private $labelDescriptionLookupFactory;

	/** @var MathFormatter */
	private $mathFormatter;

	/** @var EntityIdParser */
	private $idParser;

	/** @var PropertyId|null */
	private $propertyIdHasPart;

	/** @var PropertyId|null */
	private $propertyIdDefiningFormula;

	/** @var PropertyId|null */
	private $propertyIdInDefiningFormula;

	/** @var PropertyId|null */
	private $propertyIdQuantitySymbol;

	/** @var PropertyId|null */
	private $propertyIdSymbolRepresents;

	/**
	 * @param ServiceOptions $options
	 * @param RepoLinker $repoLinker
	 * @param LanguageFactory $languageFactory
	 * @param LanguageNameUtils $languageNameUtils
	 * @param EntityRevisionLookup $entityRevisionLookup
	 * @param FallbackLabelDescriptionLookupFactory $labelDescriptionLookupFactory
	 * @param Site $site
	 * @param EntityIdParser $entityIdParser
	 * @param MathFormatter $mathFormatter
	 * @param LoggerInterface $logger
	 */
	public function __construct(
		ServiceOptions $options,
		RepoLinker $repoLinker,
		LanguageFactory $languageFactory,
		LanguageNameUtils $languageNameUtils,
		EntityRevisionLookup $entityRevisionLookup,
		FallbackLabelDescriptionLookupFactory $labelDescriptionLookupFactory,
		Site $site,
		EntityIdParser $entityIdParser,
		MathFormatter $mathFormatter,
		LoggerInterface $logger
	) {
		$options->assertRequiredOptions( self::CONSTRUCTOR_OPTIONS );
		$this->repoLinker = $repoLinker;
		$this->languageFactory = $languageFactory;
		$this->languageNameUtils = $languageNameUtils;
		$this->entityRevisionLookup = $entityRevisionLookup;
		$this->labelDescriptionLookupFactory = $labelDescriptionLookupFactory;
		$this->site = $site;
		$this->idParser = $entityIdParser;
		$this->mathFormatter = $mathFormatter;
		$this->logger = $logger;

		$this->propertyIdHasPart = $this->loadPropertyId(
			$options->get( "MathWikibasePropertyIdHasPart" )
		);
		$this->propertyIdDefiningFormula = $this->loadPropertyId(
			$options->get( "MathWikibasePropertyIdDefiningFormula" )
		);
		$this->propertyIdInDefiningFormula = $this->loadPropertyId(
			$options->get( "MathWikibasePropertyIdInDefiningFormula" )
		);
		$this->propertyIdQuantitySymbol = $this->loadPropertyId(
			$options->get( "MathWikibasePropertyIdQuantitySymbol" )
		);
		$this->propertyIdSymbolRepresents = $this->loadPropertyId(
			$options->get( "MathWikibasePropertyIdSymbolRepresents" )
		);
	}

	/**
	 * Returns the given PropertyId if available.
	 * @param string $propertyId the string of the Wikibase property
	 * @return EntityId|null the property object or null if unavailable
	 */
	private function loadPropertyId( string $propertyId ): ?EntityId {
		try {
			return $this->idParser->parse( $propertyId );
		} catch ( ConfigException $e ) {
			return null;
		}
	}

	/**
	 * Returns the inner statements from given statements for a given property ID or an empty list if the given ID
	 * not exists.
	 * @param StatementList $statements
	 * @param PropertyId|null $id
	 * @return StatementList might be empty
	 */
	private function getStatements( StatementList $statements, ?PropertyId $id ): StatementList {
		if ( $id === null ) {
			return new StatementList();
		}
		return $statements->getByPropertyId( $id );
	}

	/**
	 * @param string $qid
	 * @param string $langCode the language to fetch data
	 *          (may fallback if requested language does not exist)
	 *
	 * @return MathWikibaseInfo the object may be empty if no information can be fetched.
	 * @throws InvalidArgumentException if the language code does not exist or the given
	 * id does not exist
	 */
	public function fetchWikibaseFromId( string $qid, string $langCode ): MathWikibaseInfo {
		if ( $this->languageNameUtils->isValidCode( $langCode ) ) {
			$lang = $this->languageFactory->getLanguage( $langCode );
		} else {
			throw new InvalidArgumentException( "Invalid language code specified." );
		}

		$langLookup = $this->labelDescriptionLookupFactory->newLabelDescriptionLookup( $lang );
		try {
			$entityId = $this->idParser->parse( $qid ); // exception if the given ID is invalid
			$entityRevision = $this->entityRevisionLookup->getEntityRevision( $entityId );
		} catch ( EntityIdParsingException $e ) {
			throw new InvalidArgumentException( "Invalid Wikibase ID." );
		} catch ( RevisionedUnresolvedRedirectException | StorageException $e ) {
			throw new InvalidArgumentException( "Non-existing Wikibase ID." );
		}

		if ( !$entityId || !$entityRevision ) {
			throw new InvalidArgumentException( "Non-existing Wikibase ID." );
		}

		$entity = $entityRevision->getEntity();
		$output = new MathWikibaseInfo( $entityId, $this->mathFormatter );

		if ( $entity instanceof Item ) {
			$this->fetchLabelDescription( $output, $langLookup );
			$this->fetchStatements( $output, $entity, $langLookup );
			return $output;
		} else { // we only allow Wikibase items
			throw new InvalidArgumentException( "The specified Wikibase ID does not represented an item." );
		}
	}

	/**
	 * Fetches only label and description from an entity.
	 * @param MathWikibaseInfo $output the entity id of the entity
	 * @param LabelDescriptionLookup $langLookup a lookup handler to fetch right languages
	 * @return MathWikibaseInfo filled up with label and description
	 */
	private function fetchLabelDescription(
		MathWikibaseInfo $output,
		LabelDescriptionLookup $langLookup ) {
		$label = $langLookup->getLabel( $output->getId() );
		$desc = $langLookup->getDescription( $output->getId() );

		if ( $label ) {
			$output->setLabel( $label->getText() );
		}

		if ( $desc ) {
			$output->setDescription( $desc->getText() );
		}

		return $output;
	}

	/**
	 * Fetches 'has part' statements from a given item element with a defined lookup object for
	 * the languages.
	 * @param MathWikibaseInfo $output the output element
	 * @param Item $item item to fetch statements from
	 * @param LabelDescriptionLookup $langLookup
	 * @return MathWikibaseInfo the updated $output object
	 */
	private function fetchStatements(
		MathWikibaseInfo $output,
		Item $item,
		LabelDescriptionLookup $langLookup ) {
		$statements = $item->getStatements();
		$formulaComponentStatements = $this->getStatements( $statements, $this->propertyIdHasPart );
		if ( $formulaComponentStatements->isEmpty() ) {
			$formulaComponentStatements = $this->getStatements( $statements, $this->propertyIdInDefiningFormula );
		}
		$this->fetchHasPartSnaks( $output, $formulaComponentStatements, $langLookup );

		$symbolStatement = $this->getStatements( $statements, $this->propertyIdDefiningFormula );
		if ( $symbolStatement->count() < 1 ) { // if it's not a formula, it might be a symbol
			$symbolStatement = $this->getStatements( $statements, $this->propertyIdQuantitySymbol );
		}
		$this->fetchSymbol( $output, $symbolStatement );
		return $output;
	}

	/**
	 * Fetches the symbol or defining formula from a statement list and adds the symbol to the
	 * given info object
	 * @param MathWikibaseInfo $output
	 * @param StatementList $statements
	 * @return MathWikibaseInfo updated object
	 */
	private function fetchSymbol( MathWikibaseInfo $output, StatementList $statements ) {
		foreach ( $statements as $statement ) {
			$snak = $statement->getMainSnak();
			if ( $snak instanceof PropertyValueSnak && $this->isSymbolSnak( $snak ) ) {
				$dataVal = $snak->getDataValue();
				$symbol = new StringValue( $dataVal->getValue() );
				$output->setSymbol( $symbol );
				return $output;
			}
		}

		return $output;
	}

	/**
	 * Fetches single snaks from 'has part' statements
	 *
	 * @param MathWikibaseInfo $output
	 * @param StatementList $statements the 'has part' statements
	 * @param LabelDescriptionLookup $langLookup
	 * @return MathWikibaseInfo
	 * @todo refactor this method once Wikibase has a more convenient way to handle snaks
	 */
	private function fetchHasPartSnaks(
		MathWikibaseInfo $output,
		StatementList $statements,
		LabelDescriptionLookup $langLookup ) {
		foreach ( $statements as $statement ) {
			$snaks = $statement->getAllSnaks();
			$innerInfo = null;
			$symbol = null;

			foreach ( $snaks as $snak ) {
				if ( $snak instanceof PropertyValueSnak ) {
					if ( $this->isSymbolSnak( $snak ) ) {
						$dataVal = $snak->getDataValue();
						$symbol = new StringValue( $dataVal->getValue() );
					} elseif ( $this->isFormulaItemSnak( $snak ) ) {
						$dataVal = $snak->getDataValue();
						$entityIdValue = $dataVal->getValue();
						if ( $entityIdValue instanceof EntityIdValue ) {
							$innerEntityId = $entityIdValue->getEntityId();
							$innerInfo = new MathWikibaseInfo( $innerEntityId, $output->getFormatter() );
							$this->fetchLabelDescription( $innerInfo, $langLookup );
							$url = $this->fetchPageUrl( $innerEntityId );
							if ( $url ) {
								$innerInfo->setUrl( $url );
							}
						}
					}
				}
			}

			if ( $innerInfo && $symbol ) {
				$innerInfo->setSymbol( $symbol );
				$output->addHasPartElement( $innerInfo );
			}
		}

		return $output;
	}

	/**
	 * Fetch the page url for a given entity id.
	 * @param EntityId $entityId
	 * @return string|false
	 */
	private function fetchPageUrl( EntityId $entityId ) {
		try {
			$entityRevision = $this->entityRevisionLookup->getEntityRevision( $entityId );
			$innerEntity = $entityRevision->getEntity();
			if ( $innerEntity instanceof Item ) {
				$globalID = $this->site->getGlobalId();
				if ( $innerEntity->hasLinkToSite( $globalID ) ) {
					$siteLink = $innerEntity->getSiteLink( $globalID );
					return $this->site->getPageUrl( $siteLink->getPageName() );
				}
			}
		} catch ( StorageException $e ) {
			$this->logger->warning(
				"Cannot fetch URL for EntityId " . $entityId . ". Reason: " . $e->getMessage()
			);
		}
		return false;
	}

	/**
	 * @param Snak $snak
	 * @return bool true if the given snak is either a defining formula, a quantity symbol, or a 'in defining formula'
	 */
	private function isSymbolSnak( Snak $snak ) {
		return $snak->getPropertyId()->equals( $this->propertyIdQuantitySymbol ) ||
			$snak->getPropertyId()->equals( $this->propertyIdDefiningFormula ) ||
			$snak->getPropertyId()->equals( $this->propertyIdInDefiningFormula );
	}

	/**
	 * @param Snak $snak
	 * @return bool true if the given snak is either the 'has part or parts' or the 'symbol represents' property
	 */
	private function isFormulaItemSnak( Snak $snak ) {
		return $snak->getPropertyId()->equals( $this->propertyIdHasPart ) ||
			$snak->getPropertyId()->equals( $this->propertyIdSymbolRepresents );
	}

	/**
	 * @param string $qID
	 * @return string
	 */
	public function buildURL( string $qID ): string {
		return $this->repoLinker->getEntityUrl( new ItemId( $qID ) );
	}
}

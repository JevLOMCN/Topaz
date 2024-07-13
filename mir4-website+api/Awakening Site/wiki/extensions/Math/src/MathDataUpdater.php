<?php

namespace MediaWiki\Extension\Math;

use MediaWiki\Parser\ParserOutput;
use Wikibase\DataModel\Services\Entity\PropertyDataTypeMatcher;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\ParserOutput\StatementDataUpdater;

/**
 * Add required styles for mathematical formulae to the ParserOutput.
 *
 * @license GPL-2.0-or-later
 * @author Moritz Schubotz
 */
class MathDataUpdater implements StatementDataUpdater {

	/** @var bool */
	private $hasMath = false;

	/**
	 * @var PropertyDataTypeMatcher
	 */
	private $propertyDataTypeMatcher;

	/**
	 * @inheritDoc
	 */
	public function __construct( PropertyDataTypeMatcher $propertyDataTypeMatcher ) {
		$this->propertyDataTypeMatcher = $propertyDataTypeMatcher;
	}

	/**
	 * Extract some data or do processing on a Statement during parsing.
	 *
	 * This method is normally invoked when processing a StatementList
	 * for all Statements on a StatementListProvider (e.g. an Item).
	 *
	 * @param Statement $statement
	 */
	public function processStatement( Statement $statement ) {
		$propertyId = $statement->getPropertyId();
		if ( $this->propertyDataTypeMatcher->isMatchingDataType( $propertyId, 'math' ) ) {
			$this->hasMath = true;
		}
	}

	/**
	 * Update extension data, properties or other data in ParserOutput.
	 * These updates are invoked when EntityContent::getParserOutput is called.
	 *
	 * @param ParserOutput $parserOutput
	 */
	public function updateParserOutput( ParserOutput $parserOutput ) {
		if ( $this->hasMath ) {
			$parserOutput->addModuleStyles( [ 'ext.math.styles' ] );
		}
	}
}

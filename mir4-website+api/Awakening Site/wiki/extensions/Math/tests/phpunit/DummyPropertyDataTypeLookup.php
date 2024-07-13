<?php

use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;

class DummyPropertyDataTypeLookup implements PropertyDataTypeLookup {
	/**
	 * @var int
	 */
	public static $mathId = 1;

	/**
	 * Returns the data type for the Property of which the id is given.
	 *
	 * @since 2.0
	 *
	 * @param \Wikibase\DataModel\Entity\PropertyId $propertyId
	 *
	 * @return string
	 * @throws \Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException
	 */
	public function getDataTypeIdForProperty( \Wikibase\DataModel\Entity\PropertyId $propertyId ) {
		return $propertyId->getNumericId() == self::$mathId ? 'math' : 'not-math';
	}
}

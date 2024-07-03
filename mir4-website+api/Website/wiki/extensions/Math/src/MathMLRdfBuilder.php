<?php

namespace MediaWiki\Extension\Math;

use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Repo\Rdf\ValueSnakRdfBuilder;
use Wikimedia\Purtle\RdfWriter;

class MathMLRdfBuilder implements ValueSnakRdfBuilder {

	/**
	 * Adds a value
	 *
	 * @param RdfWriter $writer
	 * @param string $propertyValueNamespace Property value relation namespace
	 * @param string $propertyValueLName Property value relation name
	 * @param string $dataType Property data type
	 * @param string $snakNamespace
	 * @param PropertyValueSnak $snak
	 */
	public function addValue(
		RdfWriter $writer,
		$propertyValueNamespace,
		$propertyValueLName,
		$dataType,
		$snakNamespace,
		PropertyValueSnak $snak
	) {
		$renderer = new MathMathML( $snak->getDataValue()->getValue() );
		if ( $renderer->checkTeX() && $renderer->render() ) {
			$mml = $renderer->getMathml();
		} else {
			$err = $renderer->getLastError();
			$mml = "<math xmlns=\"http://www.w3.org/1998/Math/MathML\"><merror>$err</merror></math>";
		}
		$writer->say( $propertyValueNamespace, $propertyValueLName )
			->value( $mml, 'http://www.w3.org/1998/Math/MathML' );
	}
}

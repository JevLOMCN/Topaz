<?php

namespace MediaWiki\Extension\Math\WikiTexVC\MMLnodes;

/**
 * Presentation MathML 3 Element
 * name: "menclose"
 * description: "The menclose element renders its content inside the enclosing notation specified
 * by its notation attribute."
 * category:  General Layout Schemata
 */
class MMLmenclose extends MMLbase {

	public function __construct( string $texclass = "", array $attributes = [] ) {
		parent::__construct( "menclose", $texclass, $attributes );
	}
}

<?php

namespace MediaWiki\Extension\Math\WikiTexVC\MMLnodes;

/**
 * Presentation MathML 3 Element
 * name: "mtd"
 * description: "one entry in a table or matrix"
 * category: "Tables and Matrices"
 */
class MMLmtd extends MMLbase {

	public function __construct( string $texclass = "", array $attributes = [] ) {
		parent::__construct( "mtd", $texclass, $attributes );
	}

}

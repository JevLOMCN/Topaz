<?php

namespace MediaWiki\Extension\Math\WikiTexVC\MMLnodes;

/**
 * Presentation MathML 3 Element
 * name: "mtr"
 * description: "row in a table or matrix"
 * category: "Tables and Matrices"
 */
class MMLmtr extends MMLbase {

	public function __construct( string $texclass = "", array $attributes = [] ) {
		parent::__construct( "mtr", $texclass, $attributes );
	}

}

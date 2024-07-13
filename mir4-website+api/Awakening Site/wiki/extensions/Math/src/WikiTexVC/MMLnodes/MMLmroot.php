<?php

namespace MediaWiki\Extension\Math\WikiTexVC\MMLnodes;

/**
 * Presentation MathML 3 Element
 * name: "mroot"
 * description: "form a radical with specified index"
 * category: "General Layout Schemata"
 */
class MMLmroot extends MMLbase {

	public function __construct( string $texclass = "", array $attributes = [] ) {
		parent::__construct( "mroot", $texclass, $attributes );
	}
}

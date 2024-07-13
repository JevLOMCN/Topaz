<?php

namespace MediaWiki\Extension\Math\WikiTexVC\MMLnodes;

/**
 * Presentation MathML 3 Element
 * name: "msub"
 * description: "attach a subscript to a base"
 * category: "Script and Limit Schemata"
 */
class MMLmsub extends MMLbase {
	public function __construct( string $texclass = "", array $attributes = [] ) {
		parent::__construct( "msub", $texclass, $attributes );
	}
}

<?php

namespace MediaWiki\Extension\Math\WikiTexVC\MMLnodes;

/**
 * Presentation MathML 3 Element
 * name: "munder"
 * description: "attach an underscript to a base"
 * category: "Script and Limit Schemata"
 */
class MMLmunder extends MMLbase {
	public function __construct( string $texclass = "", array $attributes = [] ) {
		parent::__construct( "munder", $texclass, $attributes );
	}
}

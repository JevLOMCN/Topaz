<?php

namespace MediaWiki\Extension\Math\WikiTexVC\MMLnodes;

/**
 * Presentation MathML 3 Element
 * name: "mstyle"
 * description: "style change"
 * category: "General Layout Schemata"
 */
class MMLmstyle extends MMLbase {

	public function __construct( string $texclass = "", array $attributes = [] ) {
		parent::__construct( "mstyle", $texclass, $attributes );
	}
}

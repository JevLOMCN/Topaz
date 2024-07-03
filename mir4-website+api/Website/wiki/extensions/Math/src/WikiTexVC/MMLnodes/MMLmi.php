<?php

namespace MediaWiki\Extension\Math\WikiTexVC\MMLnodes;

/**
 * Presentation MathML 3 Element
 * name: "mi"
 * description: "identifier"
 * category: "Token Elements"
 */
class MMLmi extends MMLbase {

	public function __construct( string $texclass = "", array $attributes = [] ) {
		parent::__construct( "mi", $texclass, $attributes );
	}
}

<?php

namespace MediaWiki\Extension\Math\WikiTexVC\MMLnodes;

/**
 * Presentation MathML 3 Element
 * name: "mn"
 * description: "number"
 * category: "Token Elements"
 */
class MMLmn extends MMLbase {

	public function __construct( string $texclass = "", array $attributes = [] ) {
		parent::__construct( "mn", $texclass, $attributes );
	}
}

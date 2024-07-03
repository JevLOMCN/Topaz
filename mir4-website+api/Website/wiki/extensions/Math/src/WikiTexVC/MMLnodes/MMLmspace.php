<?php

namespace MediaWiki\Extension\Math\WikiTexVC\MMLnodes;

/**
 * Presentation MathML 3 Element
 * name: "mspace"
 * description: "space"
 * category: "Token Elements"
 */
class MMLmspace extends MMLbase {
	public function __construct( string $texclass = "", array $attributes = [] ) {
		parent::__construct( "mspace", $texclass, $attributes );
	}
}

<?php

namespace MediaWiki\Extension\Math\WikiTexVC\MMLnodes;

/**
 * Presentation MathML 3 Element
 * name: "ms"
 * description: "string literal"
 * category: "Token Elements"
 */
class MMLms extends MMLbase {
	public function __construct( string $texclass = "", array $attributes = [] ) {
		parent::__construct( "ms", $texclass, $attributes );
	}
}

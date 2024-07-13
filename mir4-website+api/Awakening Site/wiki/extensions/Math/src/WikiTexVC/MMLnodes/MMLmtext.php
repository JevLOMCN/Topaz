<?php

namespace MediaWiki\Extension\Math\WikiTexVC\MMLnodes;

/**
 * Presentation MathML 3 Element
 * name: "mtext"
 * description: "text"
 * category: "Token Elements"
 */
class MMLmtext extends MMLbase {
	public function __construct( string $texclass = "", array $attributes = [] ) {
		parent::__construct( "mtext", $texclass, $attributes );
	}
}

<?php

namespace MediaWiki\Extension\Math\WikiTexVC\MMLnodes;

/**
 * Presentation MathML 3 Element
 * name: "mo"
 * description: "Operator, Fence, Separator or Accent"
 * category: "Token Elements"
 */
class MMLmo extends MMLbase {

	public function __construct( string $texclass = "", array $attributes = [] ) {
		parent::__construct( "mo", $texclass, $attributes );
	}
}

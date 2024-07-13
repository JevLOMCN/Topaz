<?php

namespace MediaWiki\Extension\Math\WikiTexVC\MMLnodes;

/**
 * Presentation MathML 3 Element
 * name: "mover"
 * description: "attach an overscript to a base"
 * category: "Script and Limit Schemata"
 */
class MMLmover extends MMLbase {
	public function __construct( string $texclass = "", array $attributes = [] ) {
		parent::__construct( "mover", $texclass, $attributes );
	}
}

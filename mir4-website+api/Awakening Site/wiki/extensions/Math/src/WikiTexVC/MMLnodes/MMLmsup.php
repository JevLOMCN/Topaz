<?php

namespace MediaWiki\Extension\Math\WikiTexVC\MMLnodes;

/**
 * Presentation MathML 3 Element
 * name: "msup"
 * description: "attach a superscript to a base"
 * category: "Script and Limit Schemata"
 */
class MMLmsup extends MMLbase {
	public function __construct( string $texclass = "", array $attributes = [] ) {
		parent::__construct( "msup", $texclass, $attributes );
	}
}

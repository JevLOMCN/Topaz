<?php

namespace MediaWiki\Extension\Math\WikiTexVC\MMLnodes;

/**
 * Presentation MathML 3 Element
 * name: "msubsup"
 * description: "attach a subscript-superscript pair to a base"
 * category: "Script and Limit Schemata"
 */
class MMLmsubsup extends MMLbase {
	public function __construct( string $texclass = "", array $attributes = [] ) {
		parent::__construct( "msubsup", $texclass, $attributes );
	}
}

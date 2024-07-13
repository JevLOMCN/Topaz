<?php

namespace MediaWiki\Extension\Math\WikiTexVC\MMLnodes;

/**
 * Presentation MathML 3 Element
 * name: "munderover"
 * description: "attach an underscript-overscript pair to a base"
 * category: "Script and Limit Schemata"
 */
class MMLmunderover extends MMLbase {
	public function __construct( string $texclass = "", array $attributes = [] ) {
		parent::__construct( "munderover", $texclass, $attributes );
	}
}

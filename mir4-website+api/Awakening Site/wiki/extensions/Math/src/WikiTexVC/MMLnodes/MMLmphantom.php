<?php

namespace MediaWiki\Extension\Math\WikiTexVC\MMLnodes;

/**
 * Presentation MathML 3 Element
 * name: "mphantom"
 * description:  "The mphantom element renders invisibly,
 * but with the same size and other dimensions, including baseline position,
 * that its contents would have if they were rendered normally"
 * category: General Layout Schemata
 */
class MMLmphantom extends MMLbase {

	public function __construct( string $texclass = "", array $attributes = [] ) {
		parent::__construct( "mphantom", $texclass, $attributes );
	}
}

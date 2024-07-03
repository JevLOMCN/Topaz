<?php

namespace MediaWiki\Extension\Math\WikiTexVC\MMLnodes;

/**
 * Presentation MathML 3 Element
 * name: "mpadded"
 * description:  ?
 * category:  ?
 */
class MMLmpadded extends MMLbase {

	public function __construct( string $texclass = "", array $attributes = [] ) {
		parent::__construct( "mpadded", $texclass, $attributes );
	}
}

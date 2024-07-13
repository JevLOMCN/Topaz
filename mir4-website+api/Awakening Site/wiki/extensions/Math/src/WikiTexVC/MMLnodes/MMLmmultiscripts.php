<?php

namespace MediaWiki\Extension\Math\WikiTexVC\MMLnodes;

/**
 * Presentation MathML 3 Element
 * name: "mmultiscripts"
 * description: "?"
 * category: "?"
 */
class MMLmmultiscripts extends MMLbase {

	public function __construct( string $texclass = "", array $attributes = [] ) {
		parent::__construct( "mmultiscripts", $texclass, $attributes );
	}
}

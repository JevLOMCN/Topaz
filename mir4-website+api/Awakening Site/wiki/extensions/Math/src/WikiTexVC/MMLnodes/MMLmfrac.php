<?php

namespace MediaWiki\Extension\Math\WikiTexVC\MMLnodes;

/**
 * Presentation MathML 3 Element
 * name: "mfrac"
 * description: "form a fraction from two sub-expressions"
 * category: "General Layout Schemata"
 */
class MMLmfrac extends MMLbase {

	public function __construct( string $texclass = "", array $attributes = [] ) {
		parent::__construct( 'mfrac', $texclass, $attributes );
	}
}

<?php

namespace MediaWiki\Extension\Math\WikiTexVC\MMLnodes;

class MMLmath extends MMLbase {

	public function __construct( string $texclass = "", $attributes = [] ) {
		$attributes["xmlns"] = "http://www.w3.org/1998/Math/MathML";
		parent::__construct( "math", $texclass, $attributes );
	}
}

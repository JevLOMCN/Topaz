<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\Math\WikiTexVC\Nodes;

class Dollar extends TexNode {

	public function __construct( TexArray $value ) {
		parent::__construct( $value );
	}

	public function render() {
		return '$' . parent::render() . '$';
	}

	public function extractIdentifiers( $args = null ) {
		return [];
	}

}

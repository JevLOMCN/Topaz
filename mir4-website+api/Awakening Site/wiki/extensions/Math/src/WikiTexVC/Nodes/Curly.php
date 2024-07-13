<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\Math\WikiTexVC\Nodes;

use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmrow;

class Curly extends TexNode {

	/** @var TexArray */
	private $arg;

	public function __construct( TexArray $arg ) {
		parent::__construct( $arg );
		$this->arg = $arg;
	}

	/**
	 * @return TexArray
	 */
	public function getArg(): TexArray {
		return $this->arg;
	}

	public function render() {
		return $this->arg->inCurlies();
	}

	public function renderMML( $arguments = [], $state = [] ) {
		if ( count( $this->arg->getArgs() ) > 1 ) {
			$mmlRow = new MMLmrow();
			return $mmlRow->encapsulateRaw( parent::renderMML( $arguments, $state ) );
		}

		return parent::renderMML( $arguments, $state );
	}

	public function inCurlies() {
		return $this->render();
	}

	public function extractSubscripts() {
		return $this->arg->extractSubscripts();
	}

	public function getModIdent() {
		return $this->arg->getModIdent();
	}

}

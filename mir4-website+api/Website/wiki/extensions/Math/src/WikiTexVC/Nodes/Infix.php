<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\Math\WikiTexVC\Nodes;

class Infix extends TexNode {

	/** @var string */
	private $op;
	/** @var TexArray */
	private $arg1;
	/** @var TexArray */
	private $arg2;

	public function __construct( string $op, TexArray $arg1, TexArray $arg2 ) {
		parent::__construct( $op, $arg1, $arg2 );
		$this->op = $op;
		$this->arg1 = $arg1;
		$this->arg2 = $arg2;
	}

	/**
	 * @return string
	 */
	public function getOp(): string {
		return $this->op;
	}

	/**
	 * @return TexArray
	 */
	public function getArg1(): TexArray {
		return $this->arg1;
	}

	/**
	 * @return TexArray
	 */
	public function getArg2(): TexArray {
		return $this->arg2;
	}

	public function inCurlies() {
		return $this->render();
	}

	public function render() {
		return '{' . $this->arg1->render() .
			' ' . $this->op . ' ' .
			$this->arg2->render() . '}';
	}

	public function renderMML( $arguments = [], $state = [] ) {
		return $this->parseToMML( $this->op, $arguments, null );
	}

	public function extractIdentifiers( $args = null ) {
		if ( $args == null ) {
			$args = [ $this->arg1, $this->arg2 ];
		}

		return parent::extractIdentifiers( $args );
	}

}

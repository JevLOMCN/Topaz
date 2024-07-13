<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\Math\WikiTexVC\Nodes;

use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmi;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmover;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmrow;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmsup;
use MediaWiki\Extension\Math\WikiTexVC\TexUtil;

class UQ extends TexNode {

	/** @var TexNode */
	private $base;
	/** @var TexNode */
	private $up;

	public function __construct( TexNode $base, TexNode $up ) {
		parent::__construct( $base, $up );
		$this->base = $base;
		$this->up = $up;
	}

	/**
	 * @return TexNode
	 */
	public function getBase(): TexNode {
		return $this->base;
	}

	/**
	 * @return TexNode
	 */
	public function getUp(): TexNode {
		return $this->up;
	}

	public function render() {
		return $this->base->render() . '^' . $this->up->inCurlies();
	}

	public function renderMML( $arguments = [], $state = [] ) {
		$mrow = new MMLmrow();
		$mmlBase = new MMLmsup();
		$base = $this->getBase();
		$up = $this->getUp();

		$tu = TexUtil::getInstance();

		if ( $base instanceof Fun1 && $tu->over_operator( $base->getFname() ) ) {
			$mmlBase = new MMLmover();
		}

		// If the superscript has empty elements, render them with empty mi elements to prevent browser issues
		$mi = new MMLmi();
		if ( $base instanceof TexArray && count( $base->getArgs() ) == 0 ) {
			$baseRendered = $mi->getEmpty();
		} else {
			$baseRendered = $base->renderMML( $arguments, $state );
		}
		if ( $up instanceof TexArray && count( $up->getArgs() ) == 0 ) {
			$upRendered = $mi->getEmpty();
		} else {
			// up is inferring a new mrow if it has some content
			$upRendered = $mrow->encapsulateRaw( $up->renderMML( $arguments, $state ) );
		}

		return $mmlBase->encapsulateRaw( $baseRendered . $upRendered );
	}

}

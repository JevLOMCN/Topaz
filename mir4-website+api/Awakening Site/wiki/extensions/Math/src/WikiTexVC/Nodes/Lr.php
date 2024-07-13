<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\Math\WikiTexVC\Nodes;

use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\BaseMethods;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\TexClass;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmo;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmrow;

class Lr extends TexNode {

	/** @var string */
	private $left;
	/** @var string */
	private $right;
	/** @var TexArray */
	private $arg;

	public function __construct( string $left, string $right, TexArray $arg ) {
		parent::__construct( $left, $right, $arg );
		$this->left = $left;
		$this->right = $right;
		$this->arg = $arg;
	}

	/**
	 * @return string
	 */
	public function getLeft(): string {
		return $this->left;
	}

	/**
	 * @return string
	 */
	public function getRight(): string {
		return $this->right;
	}

	/**
	 * @return TexArray
	 */
	public function getArg(): TexArray {
		return $this->arg;
	}

	public function inCurlies() {
		return '{' . $this->render() . '}';
	}

	public function render() {
		return '\\left' . $this->left . $this->arg->render() . '\\right' . $this->right;
	}

	public function renderMML( $arguments = [], $state = [] ) {
		// TBD  set attributes for right AND left correctly
		$rightAttrs = [];
		if ( $this->right == "." ) {
			$rightAttrs = [ "fence" => "true", "stretchy" => "true", "symmetric" => "true" ];
		}

		$bm = new BaseMethods();
		$left = $bm->checkAndParseDelimiter( $this->left, $this, [], null, true,
			TexClass::OPEN );
		if ( !$left ) {
			$moLeft = new MMLmo( TexClass::OPEN, [] );
			$left = $moLeft->encapsulateRaw( $this->right );
		}
		$right = $bm->checkAndParseDelimiter( $this->right, $this, $rightAttrs, null, true,
			TexClass::CLOSE );
		if ( !$right ) {
			$moRight = new MMLmo( TexClass::CLOSE, $rightAttrs );
			$right = $moRight->encapsulateRaw( $this->right );
		}

		$inner = $this->getArg()->renderMML( [], $state );
		$mrow = new MMLmrow( TexClass::INNER );
		return $mrow->encapsulateRaw(
			$left . $inner .
			$right
		);
	}

	private function mmlTranslate( $input ) {
		switch ( trim( $input ) ) {
			case "\\vert":
				return "|";
			default:
				return $input;
		}
	}

	public function containsFunc( $target, $args = null ) {
		if ( $args == null ) {
			$args = [ '\\left', '\\right', $this->arg ];
		}
		return parent::containsFunc( $target, $args );
	}

	public function extractIdentifiers( $args = null ) {
		if ( $args == null ) {
			$args = [ $this->arg ];
		}
		return parent::extractIdentifiers( $args );
	}

}

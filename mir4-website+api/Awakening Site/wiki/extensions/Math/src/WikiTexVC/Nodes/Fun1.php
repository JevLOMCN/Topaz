<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\Math\WikiTexVC\Nodes;

use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmo;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmover;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmrow;
use MediaWiki\Extension\Math\WikiTexVC\TexUtil;

class Fun1 extends TexNode {

	/** @var string */
	protected $fname;
	/** @var TexNode */
	protected $arg;
	/** @var TexUtil */
	private $tu;

	public function __construct( string $fname, TexNode $arg ) {
		parent::__construct( $fname, $arg );
		$this->fname = $fname;
		$this->arg = $arg;
		$this->tu = TexUtil::getInstance();
	}

	/**
	 * @return string
	 */
	public function getFname(): string {
		return $this->fname;
	}

	/**
	 * @return TexNode
	 */
	public function getArg(): TexNode {
		return $this->arg;
	}

	public function inCurlies() {
		return $this->render();
	}

	public function render() {
		return '{' . $this->fname . ' ' . $this->arg->inCurlies() . '}';
	}

	public function renderMML( $arguments = [], $state = [] ) {
		return $this->parseToMML( $this->fname, $arguments, null );
	}

	public function createMover( $inner, $moArgs = [] ): string {
		$mrow = new MMLmrow();
		$mo = new MMLmo( "", $moArgs );
		$mover = new MMLmover();
		$ret = $mrow->encapsulateRaw(
			$mrow->encapsulateRaw(
				$mover->encapsulateRaw(
					$this->args[1]->renderMML() .
					$mo->encapsulateRaw( $inner )
				)
			)
		);
		return $ret;
	}

	public function extractIdentifiers( $args = null ) {
		if ( $args == null ) {
			$args = [ $this->arg ];
		}
		$letterMods = array_keys( $this->tu->getBaseElements()['is_letter_mod'] );
		if ( in_array( $this->fname, $letterMods, true ) ) {
			$ident = $this->arg->getModIdent();
			if ( !isset( $ident[0] ) ) {
				return parent::extractIdentifiers( $args );
			}
			// in difference to javascript code: taking first element of array here.
			return [ $this->fname . '{' . $ident[0] . '}' ];

		} elseif ( array_key_exists( $this->fname, $this->tu->getBaseElements()['ignore_identifier'] ) ) {
			return [];
		}

		return parent::extractIdentifiers( $args );
	}

	public function extractSubscripts() {
		return $this->getSubs( $this->arg->extractSubscripts() );
	}

	public function getModIdent() {
		return $this->getSubs( $this->arg->getModIdent() );
	}

	private function getSubs( $subs ) {
		$letterMods = array_keys( $this->tu->getBaseElements()['is_letter_mod'] );

		if ( isset( $subs[0] ) && in_array( $this->fname, $letterMods, true ) ) {
			// in difference to javascript code: taking first element of array here.
			return [ $this->fname . '{' . $subs[0] . '}' ];
		}
		return [];
	}

}

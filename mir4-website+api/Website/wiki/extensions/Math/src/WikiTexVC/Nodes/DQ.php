<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\Math\WikiTexVC\Nodes;

use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\BaseParsing;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmrow;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmsub;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmunder;

class DQ extends TexNode {
	/** @var TexNode */
	private $base;
	/** @var TexNode */
	private $down;

	public function __construct( TexNode $base, TexNode $down ) {
		parent::__construct( $base, $down );
		$this->base = $base;
		$this->down = $down;
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
	public function getDown(): TexNode {
		return $this->down;
	}

	public function render() {
		return $this->base->render() . '_' . $this->down->inCurlies();
	}

	public function renderMML( $arguments = [], $state = [] ) {
		if ( array_key_exists( "limits", $state ) ) {
			// A specific DQ case with preceding limits, just invoke the limits parsing manually.
			return BaseParsing::limits( $this, $arguments, $state, "" );
		}

		$emptyMrow = "";
		// In cases with empty curly preceding like: "{}_pF_q"
		if ( $this->getBase() instanceof Curly && $this->getBase()->isEmpty() ) {
			$mrow = new MMLmrow();
			$emptyMrow = $mrow->getEmpty();
		}

		if ( !$this->isEmpty() ) {
			if ( $this->getBase()->containsFunc( "\underbrace" ) ) {
				$outer = new MMLmunder();
			} else {
				$outer = new MMLmsub();
			}
			// Otherwise use default fallback
			$mmlMrow = new MMLmrow();
			return $outer->encapsulateRaw(
				 $emptyMrow .
				$this->base->renderMML( $arguments, [ 'styleargs' => $state['styleargs'] ?? [] ] ) .
				$mmlMrow->encapsulateRaw( $this->down->renderMML( $arguments, $state ) ) );
		}

		return "";
	}

	public function extractIdentifiers( $args = null ) {
		$d = $this->down->extractSubscripts();
		$b = $this->base->extractIdentifiers();
		if ( is_array( $b ) && count( $b ) > 1 ) {
			return parent::extractIdentifiers();
		}

		if ( isset( $b[0] ) && $b[0] === '\'' ) {
			return array_merge( $b, $d );
		}

		if ( isset( $d[0] ) && isset( $b[0] ) ) {
			if ( $b[0] === '\\int' ) {
				return array_merge( $b, $d );
			}
			return [ $b[0] . '_{' . $d[0] . '}' ];
		}

		return parent::extractIdentifiers();
	}

	public function extractSubscripts() {
		$d = array_merge( [], $this->down->extractSubscripts() );
		$b = $this->base->extractSubscripts();
		if ( isset( $b[0] ) && isset( $d[0] ) ) {
			return [ $b[0] . '_{' . implode( '', $d ) . '}' ];
		}
		return parent::extractSubscripts();
	}

	public function getModIdent() {
		$d = $this->down->extractSubscripts();
		$b = $this->base->getModIdent();
		if ( isset( $b[0] ) && $b[0] === '\'' ) {
			return [];
		}
		if ( isset( $d[0] ) && isset( $b[0] ) ) {
			return [ $b[0] . '_{' . $d[0] . '}' ];
		}

		return parent::getModIdent();
	}

}

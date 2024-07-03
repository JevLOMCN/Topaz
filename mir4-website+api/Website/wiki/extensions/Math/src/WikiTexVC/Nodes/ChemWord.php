<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\Math\WikiTexVC\Nodes;

use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmrow;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmtext;

class ChemWord extends TexNode {

	/** @var TexNode */
	public $left;
	/** @var TexNode */
	public $right;

	public function __construct( TexNode $left, TexNode $right ) {
		parent::__construct( $left, $right );
		$this->left = $left;
		$this->right = $right;
	}

	/**
	 * @return TexNode
	 */
	public function getLeft(): TexNode {
		return $this->left;
	}

	/**
	 * @return TexNode
	 */
	public function getRight(): TexNode {
		return $this->right;
	}

	public function render() {
		return $this->left->render() . $this->right->render();
	}

	public function renderMML( $arguments = [], $state = [] ) {
		$mmlMrow = new MMLmrow();
		$mtextLeft = new MMLmtext( "", [ "mathcolor" => "red" ] );
		$mtextRight = new MMLmtext();
		// If right has empty literal content is resolved as dash
		$right = $this->getRight()->getArgs()[0] == "" ? "-" : $this->getRight()->renderMML( [],
			$state );
		return $mmlMrow->encapsulateRaw( $mmlMrow->encapsulateRaw(
			$mtextLeft->encapsulateRaw( $this->getLeft()->renderMML( [], $state ) )
			. $mtextRight->encapsulateRaw( $right ) ) );
	}

	public function extractIdentifiers( $args = null ) {
		return [];
	}

}

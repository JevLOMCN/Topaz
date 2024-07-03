<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\Math\WikiTexVC\Nodes;

class ChemFun2u extends TexNode {

	/** @var string */
	private $fname;
	/** @var TexNode */
	private $left;
	/** @var TexNode */
	private $right;

	/**
	 * @return string
	 */
	public function getFname(): string {
		return $this->fname;
	}

	public function __construct( string $fname, TexNode $left, TexNode $right ) {
		parent::__construct( $fname, $left, $right );
		$this->fname = $fname;
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
		return $this->fname . $this->left->inCurlies() . '_' . $this->right->inCurlies();
	}

	public function extractIdentifiers( $args = null ) {
		return [];
	}

}

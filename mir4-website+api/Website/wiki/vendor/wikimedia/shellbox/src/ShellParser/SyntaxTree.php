<?php

namespace Shellbox\ShellParser;

/**
 * A wrapper for the shell syntax tree, providing a higher-level API.
 */
class SyntaxTree {
	/** @var Node */
	private $root;

	/**
	 * @internal Use ShellParser::parse()
	 *
	 * @param Node $root
	 */
	public function __construct( $root ) {
		$this->root = $root;
	}

	/**
	 * Get the root node
	 *
	 * @return Node
	 */
	public function getRoot() {
		return $this->root;
	}

	/**
	 * Extract information about the syntax tree
	 *
	 * @return SyntaxInfo
	 */
	public function getInfo() {
		return new SyntaxInfo( $this->root );
	}
}

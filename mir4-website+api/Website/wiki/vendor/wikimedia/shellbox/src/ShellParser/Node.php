<?php

namespace Shellbox\ShellParser;

use Wikimedia\WikiPEG\InternalError;

class Node {
	/** @var string The node type */
	public $type;
	/** @var array The node contents (children) */
	public $contents;

	/**
	 * @param string $type
	 * @param array|Node|string $contents
	 */
	public function __construct( $type, $contents ) {
		$this->type = $type;
		if ( !is_array( $contents ) ) {
			$contents = [ $contents ];
		}
		foreach ( $contents as $i => $node ) {
			if ( !$node instanceof Node && !is_string( $node ) && !is_array( $node ) ) {
				$type = gettype( $node );
				throw new InternalError( 'ShellParser error: node contents validation failed. ' .
					"Item $i is a $type." );
			}
		}
		$this->contents = $contents;
	}

	/**
	 * Dump a string representation for testing or debugging
	 *
	 * @return string
	 */
	public function dump() {
		$inner = self::dumpArray( $this->contents );
		return "<{$this->type}>$inner</{$this->type}>";
	}

	/**
	 * Dump a string representation of a node array for testing or debugging
	 *
	 * @param array $array
	 * @return string
	 */
	public static function dumpArray( $array ) {
		$inner = '';
		foreach ( $array as $node ) {
			if ( $node instanceof Node ) {
				$inner .= $node->dump();
			} elseif ( is_array( $node ) ) {
				$inner .= self::dumpArray( $node );
			} else {
				$inner .= htmlspecialchars( $node, ENT_COMPAT );
			}
		}
		return $inner;
	}

	public function traverse( callable $visitor, ...$args ) {
		$visitor( $this, ...$args );
		self::traverseArray( $visitor, $this->contents, ...$args );
	}

	public static function traverseArray( callable $visitor, $array, ...$args ) {
		foreach ( $array as $node ) {
			if ( $node instanceof Node ) {
				$node->traverse( $visitor, ...$args );
			} elseif ( is_array( $node ) ) {
				self::traverseArray( $visitor, $node, ...$args );
			} else {
				$visitor( $node, ...$args );
			}
		}
	}
}

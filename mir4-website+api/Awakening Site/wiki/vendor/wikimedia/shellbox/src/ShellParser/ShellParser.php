<?php

namespace Shellbox\ShellParser;

use Wikimedia\WikiPEG\SyntaxError;

/**
 * Top-level entry for shell command parsing
 */
class ShellParser {
	/**
	 * Parse a shell command
	 *
	 * @param string $command
	 * @return SyntaxTree
	 */
	public function parse( string $command ) {
		$peg = new PEGParser;
		try {
			$node = $peg->parse( $command );
		} catch ( SyntaxError $e ) {
			throw new ShellSyntaxError( $e->getMessage(), $e->location->start, $command );
		}
		return new SyntaxTree( $node );
	}
}

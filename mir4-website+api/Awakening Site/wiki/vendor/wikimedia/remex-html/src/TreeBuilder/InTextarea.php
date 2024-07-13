<?php

namespace Wikimedia\RemexHtml\TreeBuilder;

use Wikimedia\RemexHtml\Tokenizer\Attributes;

/**
 * This is not a tree builder state in the spec. I added it to handle the
 * "next token" references in textarea. If the first token is a newline, it is
 * ignored. Then we switch the dispatcher to the "text" mode regardless, which
 * is the correct mode for parsing textarea elements.
 */
class InTextarea extends InsertionMode {
	public function characters( $text, $start, $length, $sourceStart, $sourceLength ) {
		if ( $length > 0 && $text[$start] === "\n" ) {
			// Ignore initial line break
			$start++;
			$length--;
			$sourceStart++;
			$sourceLength--;
		}
		$mode = $this->dispatcher->switchMode( Dispatcher::TEXT );
		if ( $length ) {
			$mode->characters( $text, $start, $length, $sourceStart, $sourceLength );
		}
	}

	public function endDocument( $pos ) {
		$this->dispatcher->switchMode( Dispatcher::TEXT )
			->endDocument( $pos );
	}

	public function startTag( $name, Attributes $attrs, $selfClose, $sourceStart, $sourceLength ) {
		$this->dispatcher->switchMode( Dispatcher::TEXT )
			->startTag( $name, $attrs, $selfClose, $sourceStart, $sourceLength );
	}

	public function endTag( $name, $sourceStart, $sourceLength ) {
		$this->dispatcher->switchMode( Dispatcher::TEXT )
			->endTag( $name, $sourceStart, $sourceLength );
	}

	public function doctype( $name, $public, $system, $quirks, $sourceStart, $sourceLength ) {
		$this->dispatcher->switchMode( Dispatcher::TEXT )
			->doctype( $name, $public, $system, $quirks, $sourceStart, $sourceLength );
	}

	public function comment( $text, $sourceStart, $sourceLength ) {
		$this->dispatcher->switchMode( Dispatcher::TEXT )
			->comment( $text, $sourceStart, $sourceLength );
	}
}

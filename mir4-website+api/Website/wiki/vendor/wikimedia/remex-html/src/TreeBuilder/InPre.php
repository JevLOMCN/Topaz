<?php

namespace Wikimedia\RemexHtml\TreeBuilder;

use Wikimedia\RemexHtml\Tokenizer\Attributes;

/**
 * This is not a tree builder state in the spec. I added it to handle the
 * "next token" references in pre/listing. The specified mode for parsing the
 * pre/listing is saved before entering this mode. This mode checks if the
 * first token is a newline, and then switches to the correct mode regardless.
 */
class InPre extends InsertionMode {
	public function characters( $text, $start, $length, $sourceStart, $sourceLength ) {
		if ( $length > 0 && $text[$start] === "\n" ) {
			$start++;
			$length--;
			$sourceStart++;
			$sourceLength--;
		}
		$mode = $this->dispatcher->restoreMode();
		if ( $length ) {
			$mode->characters( $text, $start, $length, $sourceStart, $sourceLength );
		}
	}

	public function endDocument( $pos ) {
		$this->dispatcher->restoreMode()
			->endDocument( $pos );
	}

	public function startTag( $name, Attributes $attrs, $selfClose, $sourceStart, $sourceLength ) {
		$this->dispatcher->restoreMode()
			->startTag( $name, $attrs, $selfClose, $sourceStart, $sourceLength );
	}

	public function endTag( $name, $sourceStart, $sourceLength ) {
		$this->dispatcher->restoreMode()
			->endTag( $name, $sourceStart, $sourceLength );
	}

	public function doctype( $name, $public, $system, $quirks, $sourceStart, $sourceLength ) {
		$this->dispatcher->restoreMode()
			->doctype( $name, $public, $system, $quirks, $sourceStart, $sourceLength );
	}

	public function comment( $text, $sourceStart, $sourceLength ) {
		$this->dispatcher->restoreMode()
			->comment( $text, $sourceStart, $sourceLength );
	}
}

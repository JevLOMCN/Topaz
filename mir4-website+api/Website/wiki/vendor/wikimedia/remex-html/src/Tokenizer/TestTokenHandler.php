<?php

namespace Wikimedia\RemexHtml\Tokenizer;

/**
 * A TokenHandler which collects events from the Tokenizer and generates an
 * array compatible with the html5lib tokenizer tests.
 */
class TestTokenHandler implements TokenHandler {
	private $tokens = [];

	public function getTokens() {
		return $this->tokens;
	}

	public function startDocument( Tokenizer $tokenizer, $fns, $fn ) {
	}

	public function endDocument( $pos ) {
	}

	public function error( $text, $pos ) {
		$this->tokens[] = 'ParseError';
	}

	public function characters( $text, $start, $length, $sourceStart, $sourceLength ) {
		$this->tokens[] = [ 'Character', substr( $text, $start, $length ) ];
	}

	public function startTag( $name, Attributes $attrs, $selfClose, $sourceStart, $sourceLength ) {
		$attrArray = $attrs->getValues();
		if ( $selfClose ) {
			$this->tokens[] = [ 'StartTag', $name, $attrArray, $selfClose ];
		} else {
			$this->tokens[] = [ 'StartTag', $name, $attrArray ];
		}
	}

	public function endTag( $name, $sourceStart, $sourceLength ) {
		$this->tokens[] = [ 'EndTag', $name ];
	}

	public function doctype( $name, $public, $system, $quirks, $sourceStart, $sourceLength ) {
		$this->tokens[] = [ 'DOCTYPE', $name, $public, $system, !$quirks ];
	}

	public function comment( $text, $sourceStart, $sourceLength ) {
		$this->tokens[] = [ 'Comment', $text ];
	}
}

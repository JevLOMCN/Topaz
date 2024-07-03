<?php

namespace Wikimedia\RemexHtml\Tokenizer;

/**
 * A simple serializer for the token stream, mostly meant for debugging.
 *
 * The main reason this shouldn't be used in production for modification of
 * HTML documents is that element names are only determined by TreeBuilder
 * and are not known here. The correct serialization for a text node depends
 * on its parent element, including its namespace.
 */
class TokenSerializer implements TokenHandler {
	private $output;
	private $errors = [];

	public function getOutput() {
		return $this->output;
	}

	public function getErrors() {
		return $this->errors;
	}

	public function startDocument( Tokenizer $tokenizer, $fns, $fn ) {
		$this->output = '';
	}

	public function endDocument( $pos ) {
	}

	public function error( $text, $pos ) {
		$this->errors[] = [ $text, $pos ];
	}

	public function characters( $text, $start, $length, $sourceStart, $sourceLength ) {
		$this->output .= htmlspecialchars( substr( $text, $start, $length ) );
	}

	public function startTag( $name, Attributes $attrs, $selfClose, $sourceStart, $sourceLength ) {
		$attrs = $attrs->getValues();
		$this->output .= "<$name";
		foreach ( $attrs as $name => $value ) {
			$this->output .= " $name=\"" . str_replace( '"', '&quot;', $value ) . '"';
		}
		if ( $selfClose ) {
			$this->output .= ' /';
		}
		$this->output .= '>';
	}

	public function endTag( $name, $sourceStart, $sourceLength ) {
		$this->output .= "</$name>";
	}

	public function doctype( $name, $public, $system, $quirks, $sourceStart, $sourceLength ) {
		$this->output .= "<!DOCTYPE $name";
		if ( strlen( $public ) ) {
			$this->output .= " PUBLIC \"$public\"";
			if ( strlen( $system ) ) {
				$this->output .= " \"$system\"";
			}
		} elseif ( strlen( $system ) ) {
			$this->output .= " SYSTEM \"$system\"";
		}
		$this->output .= '>';
		if ( $quirks ) {
			$this->output .= '<!--quirks-->';
		}
	}

	public function comment( $text, $sourceStart, $sourceLength ) {
		$this->output .= '<!--' . $text . '-->';
	}
}

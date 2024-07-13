<?php

namespace Wikimedia\RemexHtml\TreeBuilder;

use Wikimedia\RemexHtml\Tokenizer\Attributes;

/**
 * The "text" insertion mode
 */
class Text extends InsertionMode {
	public function characters( $text, $start, $length, $sourceStart, $sourceLength ) {
		$this->builder->insertCharacters( $text, $start, $length, $sourceStart, $sourceLength );
	}

	public function endDocument( $pos ) {
		$this->builder->error( 'unexpected end of input in text mode', $pos );
		$this->builder->pop( $pos, 0 );
		$this->dispatcher->restoreMode()
			->endDocument( $pos );
	}

	public function startTag( $name, Attributes $attrs, $selfClose, $sourceStart, $sourceLength ) {
		// @phan-suppress-previous-line PhanPluginNeverReturnMethod
		throw new TreeBuilderError( 'unexpected token' );
	}

	public function endTag( $name, $sourceStart, $sourceLength ) {
		// I think this is complete if we have no support for executing scripts
		$this->builder->pop( $sourceStart, $sourceLength );
		$this->dispatcher->restoreMode();
	}
}

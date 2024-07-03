<?php

namespace Wikimedia\RemexHtml\Tokenizer;

/**
 * A TokenHandler which does nothing
 */
class NullTokenHandler implements TokenHandler {
	/**
	 * @inheritDoc
	 */
	public function startDocument( Tokenizer $t, $fns, $fn ) {
	}

	/**
	 * @inheritDoc
	 */
	public function endDocument( $pos ) {
	}

	/**
	 * @inheritDoc
	 */
	public function error( $text, $pos ) {
	}

	/**
	 * @inheritDoc
	 */
	public function characters( $text, $start, $length, $sourceStart, $sourceLength ) {
	}

	/**
	 * @inheritDoc
	 */
	public function startTag( $name, Attributes $attrs, $selfClose,
		$sourceStart, $sourceLength
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function endTag( $name, $sourceStart, $sourceLength ) {
	}

	/**
	 * @inheritDoc
	 */
	public function doctype( $name, $public, $system, $quirks, $sourceStart, $sourceLength ) {
	}

	/**
	 * @inheritDoc
	 */
	public function comment( $text, $sourceStart, $sourceLength ) {
	}
}

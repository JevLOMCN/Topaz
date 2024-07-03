<?php

namespace Wikimedia\RemexHtml\TreeBuilder;

use Wikimedia\RemexHtml\Tokenizer\Attributes;

/**
 * A TreeHandler which does nothing
 */
class NullTreeHandler implements TreeHandler {
	/**
	 * @inheritDoc
	 */
	public function startDocument( $fns, $fn ) {
	}

	/**
	 * @inheritDoc
	 */
	public function endDocument( $pos ) {
	}

	/**
	 * @inheritDoc
	 */
	public function characters(
		$parent, $refNode, $text, $start, $length, $sourceStart, $sourceLength
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function insertElement( $parent, $refNode, Element $element, $void,
		$sourceStart, $sourceLength
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function endTag( Element $element, $sourceStart, $sourceLength ) {
	}

	/**
	 * @inheritDoc
	 */
	public function doctype( $name, $public, $system, $quirks, $sourceStart, $sourceLength ) {
	}

	/**
	 * @inheritDoc
	 */
	public function comment( $parent, $refNode, $text, $sourceStart, $sourceLength ) {
	}

	/**
	 * @inheritDoc
	 */
	public function error( $text, $pos ) {
	}

	/**
	 * @inheritDoc
	 */
	public function mergeAttributes( Element $element, Attributes $attrs, $sourceStart ) {
	}

	/**
	 * @inheritDoc
	 */
	public function removeNode( Element $element, $sourceStart ) {
	}

	/**
	 * @inheritDoc
	 */
	public function reparentChildren( Element $element, Element $newParent, $sourceStart ) {
	}
}

<?php

namespace Wikimedia\RemexHtml\TreeBuilder;

use Wikimedia\RemexHtml\Tokenizer\Attributes;

/**
 * A debugging helper which calls a callback function with a descriptive message
 * each time an Element node is destroyed.
 */
class DestructTracer implements TreeHandler {
	private $callback;

	public function __construct( $callback ) {
		$this->callback = $callback;
	}

	public function startDocument( $fragmentNamespace, $fragmentName ) {
	}

	public function endDocument( $pos ) {
	}

	public function characters( $preposition, $ref, $text, $start, $length,
		$sourceStart, $sourceLength
	) {
	}

	public function insertElement( $preposition, $ref, Element $element, $void,
		$sourceStart, $sourceLength
	) {
		$element->userData = new DestructTracerNode( $this->callback, $element->getDebugTag() );
	}

	public function endTag( Element $element, $sourceStart, $sourceLength ) {
	}

	public function doctype( $name, $public, $system, $quirks, $sourceStart, $sourceLength ) {
	}

	public function comment( $preposition, $ref, $text, $sourceStart, $sourceLength ) {
	}

	public function error( $text, $pos ) {
	}

	public function mergeAttributes( Element $element, Attributes $attrs, $sourceStart ) {
	}

	public function removeNode( Element $element, $sourceStart ) {
	}

	public function reparentChildren( Element $element, Element $newParent, $sourceStart ) {
		$newParent->userData = new DestructTracerNode( $this->callback, $newParent->getDebugTag() );
	}
}

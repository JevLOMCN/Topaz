<?php

namespace Wikimedia\RemexHtml\TreeBuilder;

use Wikimedia\RemexHtml\Tokenizer\Attributes;

/**
 * A TreeHandler which simply passes all events through to another handler.
 *
 * Applications can subclass this in order to modify only a few event types
 * as they pass through.
 *
 * @since 2.1.0
 */
class RelayTreeHandler implements TreeHandler {
	/** @var TreeHandler */
	protected $nextHandler;

	/**
	 * Construct a RelayTreeHandler which will call $nextHandler on all events
	 *
	 * @param TreeHandler $nextHandler
	 */
	public function __construct( TreeHandler $nextHandler ) {
		$this->nextHandler = $nextHandler;
	}

	/**
	 * @inheritDoc
	 */
	public function startDocument( $fragmentNamespace, $fragmentName ) {
		$this->nextHandler->startDocument( $fragmentNamespace, $fragmentName );
	}

	/**
	 * @inheritDoc
	 */
	public function endDocument( $pos ) {
		$this->nextHandler->endDocument( $pos );
	}

	/**
	 * @inheritDoc
	 */
	public function characters(
		$preposition, $ref, $text, $start, $length, $sourceStart, $sourceLength
	) {
		$this->nextHandler->characters( $preposition, $ref, $text, $start, $length,
			$sourceStart, $sourceLength );
	}

	/**
	 * @inheritDoc
	 */
	public function insertElement( $preposition, $ref, Element $element, $void,
		$sourceStart, $sourceLength
	) {
		$this->nextHandler->insertElement( $preposition, $ref, $element, $void,
		$sourceStart, $sourceLength );
	}

	/**
	 * @inheritDoc
	 */
	public function endTag( Element $element, $sourceStart, $sourceLength ) {
		$this->nextHandler->endTag( $element, $sourceStart, $sourceLength );
	}

	/**
	 * @inheritDoc
	 */
	public function doctype( $name, $public, $system, $quirks, $sourceStart, $sourceLength ) {
		$this->nextHandler->doctype( $name, $public, $system, $quirks,
			$sourceStart, $sourceLength );
	}

	/**
	 * @inheritDoc
	 */
	public function comment( $preposition, $ref, $text, $sourceStart, $sourceLength ) {
		$this->nextHandler->comment( $preposition, $ref, $text, $sourceStart, $sourceLength );
	}

	/**
	 * @inheritDoc
	 */
	public function error( $text, $pos ) {
		$this->nextHandler->error( $text, $pos );
	}

	/**
	 * @inheritDoc
	 */
	public function mergeAttributes( Element $element, Attributes $attrs, $sourceStart ) {
		$this->nextHandler->mergeAttributes( $element, $attrs, $sourceStart );
	}

	/**
	 * @inheritDoc
	 */
	public function removeNode( Element $element, $sourceStart ) {
		$this->nextHandler->removeNode( $element, $sourceStart );
	}

	/**
	 * @inheritDoc
	 */
	public function reparentChildren( Element $element, Element $newParent, $sourceStart ) {
		$this->nextHandler->reparentChildren( $element, $newParent, $sourceStart );
	}
}

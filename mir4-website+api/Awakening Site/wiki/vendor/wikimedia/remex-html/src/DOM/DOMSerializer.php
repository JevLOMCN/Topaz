<?php

namespace Wikimedia\RemexHtml\DOM;

use Wikimedia\RemexHtml\Serializer\AbstractSerializer;
use Wikimedia\RemexHtml\Tokenizer\Attributes;
use Wikimedia\RemexHtml\TreeBuilder\Element;

/**
 * This class providers a Serializer-like interface to DOMBuilder, allowing
 * DOMBuilder and direct serialization to be used interchangeably.
 *
 * HtmlFormatter::formatDOMNode() can be used directly if this interface is
 * not required.
 */
class DOMSerializer implements AbstractSerializer {
	private $formatter;
	private $builder;

	/**
	 * @param DOMBuilder $builder
	 * @param DOMFormatter $formatter This may be, for example, an HtmlFormatter object
	 */
	public function __construct( DOMBuilder $builder, DOMFormatter $formatter ) {
		$this->builder = $builder;
		$this->formatter = $formatter;
	}

	/**
	 * @inheritDoc
	 */
	public function getResult() {
		$fragment = $this->builder->getFragment();
		$s = '';
		foreach ( $fragment->childNodes as $child ) {
			$s .= $this->formatter->formatDOMNode( $child );
		}
		return $s;
	}

	/**
	 * @inheritDoc
	 */
	public function startDocument( $fragmentNamespace, $fragmentName ) {
		$this->builder->startDocument( $fragmentNamespace, $fragmentName );
	}

	/**
	 * @inheritDoc
	 */
	public function endDocument( $pos ) {
		$this->builder->endDocument( $pos );
	}

	/**
	 * @inheritDoc
	 */
	public function characters( $preposition, $refElement, $text, $start, $length,
		$sourceStart, $sourceLength
	) {
		$this->builder->characters( $preposition, $refElement, $text, $start, $length,
			$sourceStart, $sourceLength );
	}

	/**
	 * @inheritDoc
	 */
	public function insertElement( $preposition, $refElement, Element $element, $void,
		$sourceStart, $sourceLength
	) {
		$this->builder->insertElement( $preposition, $refElement, $element, $void,
			$sourceStart, $sourceLength );
	}

	/**
	 * @inheritDoc
	 */
	public function endTag( Element $element, $sourceStart, $sourceLength ) {
		$this->builder->endTag( $element, $sourceStart, $sourceLength );
	}

	/**
	 * @inheritDoc
	 */
	public function doctype( $name, $public, $system, $quirks, $sourceStart, $sourceLength ) {
		$this->builder->doctype( $name, $public, $system, $quirks, $sourceStart, $sourceLength );
	}

	/**
	 * @inheritDoc
	 */
	public function comment( $preposition, $refElement, $text, $sourceStart, $sourceLength ) {
		$this->builder->comment( $preposition, $refElement, $text, $sourceStart, $sourceLength );
	}

	/**
	 * @inheritDoc
	 */
	public function error( $text, $pos ) {
		$this->builder->error( $text, $pos );
	}

	/**
	 * @inheritDoc
	 */
	public function mergeAttributes( Element $element, Attributes $attrs, $sourceStart ) {
		$this->builder->mergeAttributes( $element, $attrs, $sourceStart );
	}

	/**
	 * @inheritDoc
	 */
	public function removeNode( Element $element, $sourceStart ) {
		$this->builder->removeNode( $element, $sourceStart );
	}

	/**
	 * @inheritDoc
	 */
	public function reparentChildren( Element $element, Element $newParent, $sourceStart ) {
		$this->builder->reparentChildren( $element, $newParent, $sourceStart );
	}
}

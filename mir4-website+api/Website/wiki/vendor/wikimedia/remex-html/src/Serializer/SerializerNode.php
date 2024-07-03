<?php

namespace Wikimedia\RemexHtml\Serializer;

use Wikimedia\RemexHtml\PropGuard;
use Wikimedia\RemexHtml\Tokenizer\Attributes;

class SerializerNode {
	use PropGuard;

	/** @var int The integer index into Serializer::$nodes of this node */
	public $id;

	/** @var int The integer index into Serializer::$nodes of the parent node */
	public $parentId;

	/** @var string The element namespace */
	public $namespace;

	/** @var string The element name */
	public $name;

	/** @var Attributes */
	public $attrs;

	/**
	 * @var bool The void flag as in TreeHandler::insertElement
	 * @see \Wikimedia\RemexHtml\TreeBuilder\TreeHandler::insertElement
	 */
	public $void;

	/** @var SerializerNode[] */
	public $children = [];

	/**
	 * Arbitrary user data can be placed here.
	 *
	 * @var mixed
	 */
	public $snData;

	/**
	 * @param int $id The integer index into Serializer::$nodes of this node
	 * @param int $parentId The integer index into Serializer::$nodes of the parent node
	 * @param string $namespace The XML namespace
	 * @param string $name The element name
	 * @param Attributes $attrs The element attributes
	 * @param bool $void The void flag as in TreeHandler::insertElement
	 */
	public function __construct( $id, $parentId, $namespace, $name, $attrs, $void ) {
		$this->id = $id;
		$this->parentId = $parentId;
		$this->namespace = $namespace;
		$this->name = $name;
		$this->attrs = $attrs;
		$this->void = $void;
	}

	/**
	 * Get a string identifying the node, for use in debugging.
	 * @return string
	 */
	public function getDebugTag() {
		return $this->name . '#' . $this->id;
	}
}

<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * Node
 *
 * @see https://dom.spec.whatwg.org/#interface-node
 *
 * @property int $nodeType
 * @property string $nodeName
 * @property string $baseURI
 * @property bool $isConnected
 * @property Document|null $ownerDocument
 * @property Node|null $parentNode
 * @property Element|null $parentElement
 * @property NodeList $childNodes
 * @property Node|null $firstChild
 * @property Node|null $lastChild
 * @property Node|null $previousSibling
 * @property Node|null $nextSibling
 * @property ?string $nodeValue
 * @property ?string $textContent
 * @phan-forbid-undeclared-magic-properties
 */
interface Node extends EventTarget {
	// Direct parent: EventTarget

	/** @var int */
	public const ELEMENT_NODE = 1;

	/** @var int */
	public const ATTRIBUTE_NODE = 2;

	/** @var int */
	public const TEXT_NODE = 3;

	/** @var int */
	public const CDATA_SECTION_NODE = 4;

	/** @var int */
	public const ENTITY_REFERENCE_NODE = 5;

	/** @var int */
	public const ENTITY_NODE = 6;

	/** @var int */
	public const PROCESSING_INSTRUCTION_NODE = 7;

	/** @var int */
	public const COMMENT_NODE = 8;

	/** @var int */
	public const DOCUMENT_NODE = 9;

	/** @var int */
	public const DOCUMENT_TYPE_NODE = 10;

	/** @var int */
	public const DOCUMENT_FRAGMENT_NODE = 11;

	/** @var int */
	public const NOTATION_NODE = 12;

	/**
	 * @return int
	 */
	public function getNodeType(): int;

	/**
	 * @return string
	 */
	public function getNodeName(): string;

	/**
	 * @return string
	 */
	public function getBaseURI(): string;

	/**
	 * @return bool
	 */
	public function getIsConnected(): bool;

	/**
	 * @return Document|null
	 */
	public function getOwnerDocument();

	/**
	 * @param GetRootNodeOptions|associative-array|null $options
	 * @return \Wikimedia\IDLeDOM\Node
	 */
	public function getRootNode( /* ?mixed */ $options = null );

	/**
	 * @return Node|null
	 */
	public function getParentNode();

	/**
	 * @return Element|null
	 */
	public function getParentElement();

	/**
	 * @return bool
	 */
	public function hasChildNodes(): bool;

	/**
	 * @return NodeList
	 */
	public function getChildNodes();

	/**
	 * @return Node|null
	 */
	public function getFirstChild();

	/**
	 * @return Node|null
	 */
	public function getLastChild();

	/**
	 * @return Node|null
	 */
	public function getPreviousSibling();

	/**
	 * @return Node|null
	 */
	public function getNextSibling();

	/**
	 * @return ?string
	 */
	public function getNodeValue(): ?string;

	/**
	 * @param ?string $val
	 */
	public function setNodeValue( ?string $val ): void;

	/**
	 * @return ?string
	 */
	public function getTextContent(): ?string;

	/**
	 * @param ?string $val
	 */
	public function setTextContent( ?string $val ): void;

	/**
	 * @return void
	 */
	public function normalize(): void;

	/**
	 * @param bool $deep
	 * @return \Wikimedia\IDLeDOM\Node
	 */
	public function cloneNode( bool $deep = false );

	/**
	 * @param \Wikimedia\IDLeDOM\Node|null $otherNode
	 * @return bool
	 */
	public function isEqualNode( /* ?\Wikimedia\IDLeDOM\Node */ $otherNode ): bool;

	/**
	 * @param \Wikimedia\IDLeDOM\Node|null $otherNode
	 * @return bool
	 */
	public function isSameNode( /* ?\Wikimedia\IDLeDOM\Node */ $otherNode ): bool;

	/** @var int */
	public const DOCUMENT_POSITION_DISCONNECTED = 0x01;

	/** @var int */
	public const DOCUMENT_POSITION_PRECEDING = 0x02;

	/** @var int */
	public const DOCUMENT_POSITION_FOLLOWING = 0x04;

	/** @var int */
	public const DOCUMENT_POSITION_CONTAINS = 0x08;

	/** @var int */
	public const DOCUMENT_POSITION_CONTAINED_BY = 0x10;

	/** @var int */
	public const DOCUMENT_POSITION_IMPLEMENTATION_SPECIFIC = 0x20;

	/**
	 * @param \Wikimedia\IDLeDOM\Node $other
	 * @return int
	 */
	public function compareDocumentPosition( /* \Wikimedia\IDLeDOM\Node */ $other ): int;

	/**
	 * @param \Wikimedia\IDLeDOM\Node|null $other
	 * @return bool
	 */
	public function contains( /* ?\Wikimedia\IDLeDOM\Node */ $other ): bool;

	/**
	 * @param ?string $namespace
	 * @return ?string
	 */
	public function lookupPrefix( ?string $namespace ): ?string;

	/**
	 * @param ?string $prefix
	 * @return ?string
	 */
	public function lookupNamespaceURI( ?string $prefix ): ?string;

	/**
	 * @param ?string $namespace
	 * @return bool
	 */
	public function isDefaultNamespace( ?string $namespace ): bool;

	/**
	 * @param \Wikimedia\IDLeDOM\Node $node
	 * @param \Wikimedia\IDLeDOM\Node|null $child
	 * @return \Wikimedia\IDLeDOM\Node
	 */
	public function insertBefore( /* \Wikimedia\IDLeDOM\Node */ $node, /* ?\Wikimedia\IDLeDOM\Node */ $child = null );

	/**
	 * @param \Wikimedia\IDLeDOM\Node $node
	 * @return \Wikimedia\IDLeDOM\Node
	 */
	public function appendChild( /* \Wikimedia\IDLeDOM\Node */ $node );

	/**
	 * @param \Wikimedia\IDLeDOM\Node $node
	 * @param \Wikimedia\IDLeDOM\Node $child
	 * @return \Wikimedia\IDLeDOM\Node
	 */
	public function replaceChild( /* \Wikimedia\IDLeDOM\Node */ $node, /* \Wikimedia\IDLeDOM\Node */ $child );

	/**
	 * @param \Wikimedia\IDLeDOM\Node $child
	 * @return \Wikimedia\IDLeDOM\Node
	 */
	public function removeChild( /* \Wikimedia\IDLeDOM\Node */ $child );

}

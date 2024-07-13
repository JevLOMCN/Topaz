<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * Element
 *
 * @see https://dom.spec.whatwg.org/#interface-element
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
 * @property string $innerHTML
 * @property Element|null $previousElementSibling
 * @property Element|null $nextElementSibling
 * @property HTMLCollection $children
 * @property Element|null $firstElementChild
 * @property Element|null $lastElementChild
 * @property int $childElementCount
 * @property HTMLSlotElement|null $assignedSlot
 * @property ?string $namespaceURI
 * @property ?string $prefix
 * @property string $localName
 * @property string $tagName
 * @property string $id
 * @property string $className
 * @property DOMTokenList $classList
 * @property string $slot
 * @property NamedNodeMap $attributes
 * @property ShadowRoot|null $shadowRoot
 * @property string $outerHTML
 * @phan-forbid-undeclared-magic-properties
 */
interface Element extends Node, ChildNode, InnerHTML, NonDocumentTypeChildNode, ParentNode, Slottable {
	// Direct parent: Node

	/**
	 * @return ?string
	 */
	public function getNamespaceURI(): ?string;

	/**
	 * @return ?string
	 */
	public function getPrefix(): ?string;

	/**
	 * @return string
	 */
	public function getLocalName(): string;

	/**
	 * @return string
	 */
	public function getTagName(): string;

	/**
	 * @return string
	 */
	public function getId(): string;

	/**
	 * @param string $val
	 */
	public function setId( string $val ): void;

	/**
	 * @return string
	 */
	public function getClassName(): string;

	/**
	 * @param string $val
	 */
	public function setClassName( string $val ): void;

	/**
	 * @return DOMTokenList
	 */
	public function getClassList();

	/**
	 * @param string $val
	 */
	public function setClassList( string $val ): void;

	/**
	 * @return string
	 */
	public function getSlot(): string;

	/**
	 * @param string $val
	 */
	public function setSlot( string $val ): void;

	/**
	 * @return bool
	 */
	public function hasAttributes(): bool;

	/**
	 * @return NamedNodeMap
	 */
	public function getAttributes();

	/**
	 * @return list<string>
	 */
	public function getAttributeNames(): array;

	/**
	 * @param string $qualifiedName
	 * @return ?string
	 */
	public function getAttribute( string $qualifiedName ): ?string;

	/**
	 * @param ?string $namespace
	 * @param string $localName
	 * @return ?string
	 */
	public function getAttributeNS( ?string $namespace, string $localName ): ?string;

	/**
	 * @param string $qualifiedName
	 * @param string $value
	 * @return void
	 */
	public function setAttribute( string $qualifiedName, string $value ): void;

	/**
	 * @param ?string $namespace
	 * @param string $qualifiedName
	 * @param string $value
	 * @return void
	 */
	public function setAttributeNS( ?string $namespace, string $qualifiedName, string $value ): void;

	/**
	 * @param string $qualifiedName
	 * @return void
	 */
	public function removeAttribute( string $qualifiedName ): void;

	/**
	 * @param ?string $namespace
	 * @param string $localName
	 * @return void
	 */
	public function removeAttributeNS( ?string $namespace, string $localName ): void;

	/**
	 * @param string $qualifiedName
	 * @param ?bool $force
	 * @return bool
	 */
	public function toggleAttribute( string $qualifiedName, ?bool $force = null ): bool;

	/**
	 * @param string $qualifiedName
	 * @return bool
	 */
	public function hasAttribute( string $qualifiedName ): bool;

	/**
	 * @param ?string $namespace
	 * @param string $localName
	 * @return bool
	 */
	public function hasAttributeNS( ?string $namespace, string $localName ): bool;

	/**
	 * @param string $qualifiedName
	 * @return Attr|null
	 */
	public function getAttributeNode( string $qualifiedName );

	/**
	 * @param ?string $namespace
	 * @param string $localName
	 * @return Attr|null
	 */
	public function getAttributeNodeNS( ?string $namespace, string $localName );

	/**
	 * @param Attr $attr
	 * @return Attr|null
	 */
	public function setAttributeNode( /* Attr */ $attr );

	/**
	 * @param Attr $attr
	 * @return Attr|null
	 */
	public function setAttributeNodeNS( /* Attr */ $attr );

	/**
	 * @param Attr $attr
	 * @return Attr
	 */
	public function removeAttributeNode( /* Attr */ $attr );

	/**
	 * @param ShadowRootInit|associative-array $init
	 * @return ShadowRoot
	 */
	public function attachShadow( /* mixed */ $init );

	/**
	 * @return ShadowRoot|null
	 */
	public function getShadowRoot();

	/**
	 * @param string $selectors
	 * @return \Wikimedia\IDLeDOM\Element|null
	 */
	public function closest( string $selectors );

	/**
	 * @param string $selectors
	 * @return bool
	 */
	public function matches( string $selectors ): bool;

	/**
	 * @param string $selectors
	 * @return bool
	 */
	public function webkitMatchesSelector( string $selectors ): bool;

	/**
	 * @param string $qualifiedName
	 * @return HTMLCollection
	 */
	public function getElementsByTagName( string $qualifiedName );

	/**
	 * @param ?string $namespace
	 * @param string $localName
	 * @return HTMLCollection
	 */
	public function getElementsByTagNameNS( ?string $namespace, string $localName );

	/**
	 * @param string $classNames
	 * @return HTMLCollection
	 */
	public function getElementsByClassName( string $classNames );

	/**
	 * @param string $where
	 * @param \Wikimedia\IDLeDOM\Element $element
	 * @return \Wikimedia\IDLeDOM\Element|null
	 */
	public function insertAdjacentElement( string $where, /* \Wikimedia\IDLeDOM\Element */ $element );

	/**
	 * @param string $where
	 * @param string $data
	 * @return void
	 */
	public function insertAdjacentText( string $where, string $data ): void;

	/**
	 * @return string
	 */
	public function getOuterHTML(): string;

	/**
	 * @param ?string $val
	 */
	public function setOuterHTML( ?string $val ): void;

	/**
	 * @param string $position
	 * @param string $text
	 * @return void
	 */
	public function insertAdjacentHTML( string $position, string $text ): void;

	/**
	 * @param string $qualifiedName
	 * @param bool $isId
	 * @return void
	 */
	public function setIdAttribute( string $qualifiedName, bool $isId ): void;

	/**
	 * @param Attr $attr
	 * @param bool $isId
	 * @return void
	 */
	public function setIdAttributeNode( /* Attr */ $attr, bool $isId ): void;

	/**
	 * @param string $namespace
	 * @param string $qualifiedName
	 * @param bool $isId
	 * @return void
	 */
	public function setIdAttributeNS( string $namespace, string $qualifiedName, bool $isId ): void;

}

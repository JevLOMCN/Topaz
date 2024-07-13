<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * Attr
 *
 * @see https://dom.spec.whatwg.org/#interface-attr
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
 * @property ?string $namespaceURI
 * @property ?string $prefix
 * @property string $localName
 * @property string $name
 * @property string $value
 * @property Element|null $ownerElement
 * @property bool $specified
 * @phan-forbid-undeclared-magic-properties
 */
interface Attr extends Node {
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
	public function getName(): string;

	/**
	 * @return string
	 */
	public function getValue(): string;

	/**
	 * @param string $val
	 */
	public function setValue( string $val ): void;

	/**
	 * @return Element|null
	 */
	public function getOwnerElement();

	/**
	 * @return bool
	 */
	public function getSpecified(): bool;

}

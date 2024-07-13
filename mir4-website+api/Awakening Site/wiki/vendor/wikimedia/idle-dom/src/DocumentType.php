<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * DocumentType
 *
 * @see https://dom.spec.whatwg.org/#interface-documenttype
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
 * @property string $name
 * @property string $publicId
 * @property string $systemId
 * @phan-forbid-undeclared-magic-properties
 */
interface DocumentType extends Node, ChildNode {
	// Direct parent: Node

	/**
	 * @return string
	 */
	public function getName(): string;

	/**
	 * @return string
	 */
	public function getPublicId(): string;

	/**
	 * @return string
	 */
	public function getSystemId(): string;

}

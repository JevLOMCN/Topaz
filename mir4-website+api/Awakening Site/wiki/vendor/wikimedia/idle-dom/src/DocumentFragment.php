<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * DocumentFragment
 *
 * @see https://dom.spec.whatwg.org/#interface-documentfragment
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
 * @property HTMLCollection $children
 * @property Element|null $firstElementChild
 * @property Element|null $lastElementChild
 * @property int $childElementCount
 * @phan-forbid-undeclared-magic-properties
 */
interface DocumentFragment extends Node, NonElementParentNode, ParentNode {
	// Direct parent: Node

	/**
	 * @param string $data
	 * @return bool
	 */
	public function appendXML( string $data ): bool;

}

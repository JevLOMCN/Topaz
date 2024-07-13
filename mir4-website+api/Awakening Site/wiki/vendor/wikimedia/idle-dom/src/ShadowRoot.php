<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * ShadowRoot
 *
 * @see https://dom.spec.whatwg.org/#interface-shadowroot
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
 * @property StyleSheetList $styleSheets
 * @property string $innerHTML
 * @property string $mode
 * @property Element $host
 * @property EventHandlerNonNull|callable|null $onslotchange
 * @phan-forbid-undeclared-magic-properties
 */
interface ShadowRoot extends DocumentFragment, DocumentOrShadowRoot, InnerHTML {
	// Direct parent: DocumentFragment

	/**
	 * @return string
	 */
	public function getMode(): /* ShadowRootMode */ string;

	/**
	 * @return Element
	 */
	public function getHost();

	/**
	 * @return EventHandlerNonNull|callable|null
	 */
	public function getOnslotchange();

	/**
	 * @param EventHandlerNonNull|callable|null $val
	 */
	public function setOnslotchange( /* ?mixed */ $val ): void;

}

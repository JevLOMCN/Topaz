<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * HTMLTableColElement
 *
 * @see https://dom.spec.whatwg.org/#interface-htmltablecolelement
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
 * @property CSSStyleDeclaration $style
 * @property string $contentEditable
 * @property string $enterKeyHint
 * @property bool $isContentEditable
 * @property string $inputMode
 * @property EventHandlerNonNull|callable|null $onload
 * @property DOMStringMap $dataset
 * @property string $nonce
 * @property int $tabIndex
 * @property string $title
 * @property string $lang
 * @property bool $translate
 * @property string $dir
 * @property bool $hidden
 * @property string $accessKey
 * @property string $accessKeyLabel
 * @property bool $draggable
 * @property bool $spellcheck
 * @property string $autocapitalize
 * @property string $innerText
 * @property Element|null $offsetParent
 * @property int $offsetTop
 * @property int $offsetLeft
 * @property int $offsetWidth
 * @property int $offsetHeight
 * @property int $span
 * @property string $align
 * @property string $ch
 * @property string $chOff
 * @property string $vAlign
 * @property string $width
 * @phan-forbid-undeclared-magic-properties
 */
interface HTMLTableColElement extends HTMLElement {
	// Direct parent: HTMLElement

	/**
	 * @return int
	 */
	public function getSpan(): int;

	/**
	 * @param int $val
	 */
	public function setSpan( int $val ): void;

	/**
	 * @return string
	 */
	public function getAlign(): string;

	/**
	 * @param string $val
	 */
	public function setAlign( string $val ): void;

	/**
	 * @return string
	 */
	public function getCh(): string;

	/**
	 * @param string $val
	 */
	public function setCh( string $val ): void;

	/**
	 * @return string
	 */
	public function getChOff(): string;

	/**
	 * @param string $val
	 */
	public function setChOff( string $val ): void;

	/**
	 * @return string
	 */
	public function getVAlign(): string;

	/**
	 * @param string $val
	 */
	public function setVAlign( string $val ): void;

	/**
	 * @return string
	 */
	public function getWidth(): string;

	/**
	 * @param string $val
	 */
	public function setWidth( string $val ): void;

}

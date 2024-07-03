<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * HTMLBodyElement
 *
 * @see https://dom.spec.whatwg.org/#interface-htmlbodyelement
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
 * @property string $text
 * @property string $link
 * @property string $vLink
 * @property string $aLink
 * @property string $bgColor
 * @property string $background
 * @phan-forbid-undeclared-magic-properties
 */
interface HTMLBodyElement extends HTMLElement, WindowEventHandlers {
	// Direct parent: HTMLElement

	/**
	 * @return string
	 */
	public function getText(): string;

	/**
	 * @param ?string $val
	 */
	public function setText( ?string $val ): void;

	/**
	 * @return string
	 */
	public function getLink(): string;

	/**
	 * @param ?string $val
	 */
	public function setLink( ?string $val ): void;

	/**
	 * @return string
	 */
	public function getVLink(): string;

	/**
	 * @param ?string $val
	 */
	public function setVLink( ?string $val ): void;

	/**
	 * @return string
	 */
	public function getALink(): string;

	/**
	 * @param ?string $val
	 */
	public function setALink( ?string $val ): void;

	/**
	 * @return string
	 */
	public function getBgColor(): string;

	/**
	 * @param ?string $val
	 */
	public function setBgColor( ?string $val ): void;

	/**
	 * @return string
	 */
	public function getBackground(): string;

	/**
	 * @param string $val
	 */
	public function setBackground( string $val ): void;

}

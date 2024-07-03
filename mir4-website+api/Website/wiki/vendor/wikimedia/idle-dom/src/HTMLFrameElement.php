<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * HTMLFrameElement
 *
 * @see https://dom.spec.whatwg.org/#interface-htmlframeelement
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
 * @property string $name
 * @property string $scrolling
 * @property string $src
 * @property string $frameBorder
 * @property string $longDesc
 * @property bool $noResize
 * @property Document|null $contentDocument
 * @property string $marginHeight
 * @property string $marginWidth
 * @phan-forbid-undeclared-magic-properties
 */
interface HTMLFrameElement extends HTMLElement {
	// Direct parent: HTMLElement

	/**
	 * @return string
	 */
	public function getName(): string;

	/**
	 * @param string $val
	 */
	public function setName( string $val ): void;

	/**
	 * @return string
	 */
	public function getScrolling(): string;

	/**
	 * @param string $val
	 */
	public function setScrolling( string $val ): void;

	/**
	 * @return string
	 */
	public function getSrc(): string;

	/**
	 * @param string $val
	 */
	public function setSrc( string $val ): void;

	/**
	 * @return string
	 */
	public function getFrameBorder(): string;

	/**
	 * @param string $val
	 */
	public function setFrameBorder( string $val ): void;

	/**
	 * @return string
	 */
	public function getLongDesc(): string;

	/**
	 * @param string $val
	 */
	public function setLongDesc( string $val ): void;

	/**
	 * @return bool
	 */
	public function getNoResize(): bool;

	/**
	 * @param bool $val
	 */
	public function setNoResize( bool $val ): void;

	/**
	 * @return Document|null
	 */
	public function getContentDocument();

	/**
	 * @return string
	 */
	public function getMarginHeight(): string;

	/**
	 * @param ?string $val
	 */
	public function setMarginHeight( ?string $val ): void;

	/**
	 * @return string
	 */
	public function getMarginWidth(): string;

	/**
	 * @param ?string $val
	 */
	public function setMarginWidth( ?string $val ): void;

}

<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * HTMLIFrameElement
 *
 * @see https://dom.spec.whatwg.org/#interface-htmliframeelement
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
 * @property string $referrerPolicy
 * @property string $src
 * @property string $srcdoc
 * @property string $name
 * @property DOMTokenList $sandbox
 * @property string $allow
 * @property bool $allowFullscreen
 * @property string $width
 * @property string $height
 * @property string $loading
 * @property Document|null $contentDocument
 * @property string $align
 * @property string $scrolling
 * @property string $frameBorder
 * @property string $longDesc
 * @property string $marginHeight
 * @property string $marginWidth
 * @phan-forbid-undeclared-magic-properties
 */
interface HTMLIFrameElement extends HTMLElement, ReferrerPolicy {
	// Direct parent: HTMLElement

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
	public function getSrcdoc(): string;

	/**
	 * @param string $val
	 */
	public function setSrcdoc( string $val ): void;

	/**
	 * @return string
	 */
	public function getName(): string;

	/**
	 * @param string $val
	 */
	public function setName( string $val ): void;

	/**
	 * @return DOMTokenList
	 */
	public function getSandbox();

	/**
	 * @param string $val
	 */
	public function setSandbox( string $val ): void;

	/**
	 * @return string
	 */
	public function getAllow(): string;

	/**
	 * @param string $val
	 */
	public function setAllow( string $val ): void;

	/**
	 * @return bool
	 */
	public function getAllowFullscreen(): bool;

	/**
	 * @param bool $val
	 */
	public function setAllowFullscreen( bool $val ): void;

	/**
	 * @return string
	 */
	public function getWidth(): string;

	/**
	 * @param string $val
	 */
	public function setWidth( string $val ): void;

	/**
	 * @return string
	 */
	public function getHeight(): string;

	/**
	 * @param string $val
	 */
	public function setHeight( string $val ): void;

	/**
	 * @return string
	 */
	public function getLoading(): string;

	/**
	 * @param string $val
	 */
	public function setLoading( string $val ): void;

	/**
	 * @return Document|null
	 */
	public function getContentDocument();

	/**
	 * @return Document|null
	 */
	public function getSVGDocument();

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
	public function getScrolling(): string;

	/**
	 * @param string $val
	 */
	public function setScrolling( string $val ): void;

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

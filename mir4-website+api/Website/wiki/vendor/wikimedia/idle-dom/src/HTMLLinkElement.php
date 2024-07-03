<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * HTMLLinkElement
 *
 * @see https://dom.spec.whatwg.org/#interface-htmllinkelement
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
 * @property ?string $crossOrigin
 * @property CSSStyleSheet|null $sheet
 * @property string $referrerPolicy
 * @property string $href
 * @property string $rel
 * @property string $as
 * @property DOMTokenList $relList
 * @property string $media
 * @property string $hreflang
 * @property string $type
 * @property DOMTokenList $sizes
 * @property string $charset
 * @property string $rev
 * @property string $target
 * @phan-forbid-undeclared-magic-properties
 */
interface HTMLLinkElement extends HTMLElement, CrossOrigin, LinkStyle, ReferrerPolicy {
	// Direct parent: HTMLElement

	/**
	 * @return string
	 */
	public function getHref(): string;

	/**
	 * @param string $val
	 */
	public function setHref( string $val ): void;

	/**
	 * @return string
	 */
	public function getRel(): string;

	/**
	 * @param string $val
	 */
	public function setRel( string $val ): void;

	/**
	 * @return string
	 */
	public function getAs(): string;

	/**
	 * @param string $val
	 */
	public function setAs( string $val ): void;

	/**
	 * @return DOMTokenList
	 */
	public function getRelList();

	/**
	 * @param string $val
	 */
	public function setRelList( string $val ): void;

	/**
	 * @return string
	 */
	public function getMedia(): string;

	/**
	 * @param string $val
	 */
	public function setMedia( string $val ): void;

	/**
	 * @return string
	 */
	public function getHreflang(): string;

	/**
	 * @param string $val
	 */
	public function setHreflang( string $val ): void;

	/**
	 * @return string
	 */
	public function getType(): string;

	/**
	 * @param string $val
	 */
	public function setType( string $val ): void;

	/**
	 * @return DOMTokenList
	 */
	public function getSizes();

	/**
	 * @param string $val
	 */
	public function setSizes( string $val ): void;

	/**
	 * @return string
	 */
	public function getCharset(): string;

	/**
	 * @param string $val
	 */
	public function setCharset( string $val ): void;

	/**
	 * @return string
	 */
	public function getRev(): string;

	/**
	 * @param string $val
	 */
	public function setRev( string $val ): void;

	/**
	 * @return string
	 */
	public function getTarget(): string;

	/**
	 * @param string $val
	 */
	public function setTarget( string $val ): void;

}

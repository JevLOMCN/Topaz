<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * HTMLMarqueeElement
 *
 * @see https://dom.spec.whatwg.org/#interface-htmlmarqueeelement
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
 * @property string $behavior
 * @property string $bgColor
 * @property string $direction
 * @property string $height
 * @property int $hspace
 * @property int $scrollAmount
 * @property int $scrollDelay
 * @property bool $trueSpeed
 * @property int $vspace
 * @property string $width
 * @phan-forbid-undeclared-magic-properties
 */
interface HTMLMarqueeElement extends HTMLElement {
	// Direct parent: HTMLElement

	/**
	 * @return string
	 */
	public function getBehavior(): string;

	/**
	 * @param string $val
	 */
	public function setBehavior( string $val ): void;

	/**
	 * @return string
	 */
	public function getBgColor(): string;

	/**
	 * @param string $val
	 */
	public function setBgColor( string $val ): void;

	/**
	 * @return string
	 */
	public function getDirection(): string;

	/**
	 * @param string $val
	 */
	public function setDirection( string $val ): void;

	/**
	 * @return string
	 */
	public function getHeight(): string;

	/**
	 * @param string $val
	 */
	public function setHeight( string $val ): void;

	/**
	 * @return int
	 */
	public function getHspace(): int;

	/**
	 * @param int $val
	 */
	public function setHspace( int $val ): void;

	/**
	 * @return int
	 */
	public function getScrollAmount(): int;

	/**
	 * @param int $val
	 */
	public function setScrollAmount( int $val ): void;

	/**
	 * @return int
	 */
	public function getScrollDelay(): int;

	/**
	 * @param int $val
	 */
	public function setScrollDelay( int $val ): void;

	/**
	 * @return bool
	 */
	public function getTrueSpeed(): bool;

	/**
	 * @param bool $val
	 */
	public function setTrueSpeed( bool $val ): void;

	/**
	 * @return int
	 */
	public function getVspace(): int;

	/**
	 * @param int $val
	 */
	public function setVspace( int $val ): void;

	/**
	 * @return string
	 */
	public function getWidth(): string;

	/**
	 * @param string $val
	 */
	public function setWidth( string $val ): void;

}

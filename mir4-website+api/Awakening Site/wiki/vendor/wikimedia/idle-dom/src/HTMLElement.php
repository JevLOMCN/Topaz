<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * HTMLElement
 *
 * @see https://dom.spec.whatwg.org/#interface-htmlelement
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
 * @phan-forbid-undeclared-magic-properties
 */
interface HTMLElement extends Element, DocumentAndElementEventHandlers, ElementCSSInlineStyle, ElementContentEditable, GlobalEventHandlers, HTMLOrSVGElement {
	// Direct parent: Element

	/**
	 * @return string
	 */
	public function getTitle(): string;

	/**
	 * @param string $val
	 */
	public function setTitle( string $val ): void;

	/**
	 * @return string
	 */
	public function getLang(): string;

	/**
	 * @param string $val
	 */
	public function setLang( string $val ): void;

	/**
	 * @return bool
	 */
	public function getTranslate(): bool;

	/**
	 * @param bool $val
	 */
	public function setTranslate( bool $val ): void;

	/**
	 * @return string
	 */
	public function getDir(): string;

	/**
	 * @param string $val
	 */
	public function setDir( string $val ): void;

	/**
	 * @return bool
	 */
	public function getHidden(): bool;

	/**
	 * @param bool $val
	 */
	public function setHidden( bool $val ): void;

	/**
	 * @return void
	 */
	public function click(): void;

	/**
	 * @return string
	 */
	public function getAccessKey(): string;

	/**
	 * @param string $val
	 */
	public function setAccessKey( string $val ): void;

	/**
	 * @return string
	 */
	public function getAccessKeyLabel(): string;

	/**
	 * @return bool
	 */
	public function getDraggable(): bool;

	/**
	 * @param bool $val
	 */
	public function setDraggable( bool $val ): void;

	/**
	 * @return bool
	 */
	public function getSpellcheck(): bool;

	/**
	 * @param bool $val
	 */
	public function setSpellcheck( bool $val ): void;

	/**
	 * @return string
	 */
	public function getAutocapitalize(): string;

	/**
	 * @param string $val
	 */
	public function setAutocapitalize( string $val ): void;

	/**
	 * @return string
	 */
	public function getInnerText(): string;

	/**
	 * @param ?string $val
	 */
	public function setInnerText( ?string $val ): void;

	/**
	 * @return Element|null
	 */
	public function getOffsetParent();

	/**
	 * @return int
	 */
	public function getOffsetTop(): int;

	/**
	 * @return int
	 */
	public function getOffsetLeft(): int;

	/**
	 * @return int
	 */
	public function getOffsetWidth(): int;

	/**
	 * @return int
	 */
	public function getOffsetHeight(): int;

}

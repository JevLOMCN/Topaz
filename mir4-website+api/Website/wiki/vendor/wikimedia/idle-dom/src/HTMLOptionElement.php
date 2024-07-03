<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * HTMLOptionElement
 *
 * @see https://dom.spec.whatwg.org/#interface-htmloptionelement
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
 * @property bool $disabled
 * @property HTMLFormElement|null $form
 * @property string $label
 * @property bool $defaultSelected
 * @property bool $selected
 * @property string $value
 * @property string $text
 * @property int $index
 * @phan-forbid-undeclared-magic-properties
 */
interface HTMLOptionElement extends HTMLElement {
	// Direct parent: HTMLElement

	/**
	 * @return bool
	 */
	public function getDisabled(): bool;

	/**
	 * @param bool $val
	 */
	public function setDisabled( bool $val ): void;

	/**
	 * @return HTMLFormElement|null
	 */
	public function getForm();

	/**
	 * @return string
	 */
	public function getLabel(): string;

	/**
	 * @param string $val
	 */
	public function setLabel( string $val ): void;

	/**
	 * @return bool
	 */
	public function getDefaultSelected(): bool;

	/**
	 * @param bool $val
	 */
	public function setDefaultSelected( bool $val ): void;

	/**
	 * @return bool
	 */
	public function getSelected(): bool;

	/**
	 * @param bool $val
	 */
	public function setSelected( bool $val ): void;

	/**
	 * @return string
	 */
	public function getValue(): string;

	/**
	 * @param string $val
	 */
	public function setValue( string $val ): void;

	/**
	 * @return string
	 */
	public function getText(): string;

	/**
	 * @param string $val
	 */
	public function setText( string $val ): void;

	/**
	 * @return int
	 */
	public function getIndex(): int;

}

<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * HTMLFormElement
 *
 * @see https://dom.spec.whatwg.org/#interface-htmlformelement
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
 * @property string $acceptCharset
 * @property string $action
 * @property string $autocomplete
 * @property string $enctype
 * @property string $encoding
 * @property string $method
 * @property string $name
 * @property bool $noValidate
 * @property string $target
 * @property HTMLFormControlsCollection $elements
 * @property int $length
 * @phan-forbid-undeclared-magic-properties
 */
interface HTMLFormElement extends HTMLElement {
	// Direct parent: HTMLElement

	/**
	 * @return string
	 */
	public function getAcceptCharset(): string;

	/**
	 * @param string $val
	 */
	public function setAcceptCharset( string $val ): void;

	/**
	 * @return string
	 */
	public function getAction(): string;

	/**
	 * @param string $val
	 */
	public function setAction( string $val ): void;

	/**
	 * @return string
	 */
	public function getAutocomplete(): string;

	/**
	 * @param string $val
	 */
	public function setAutocomplete( string $val ): void;

	/**
	 * @return string
	 */
	public function getEnctype(): string;

	/**
	 * @param string $val
	 */
	public function setEnctype( string $val ): void;

	/**
	 * @return string
	 */
	public function getEncoding(): string;

	/**
	 * @param string $val
	 */
	public function setEncoding( string $val ): void;

	/**
	 * @return string
	 */
	public function getMethod(): string;

	/**
	 * @param string $val
	 */
	public function setMethod( string $val ): void;

	/**
	 * @return string
	 */
	public function getName(): string;

	/**
	 * @param string $val
	 */
	public function setName( string $val ): void;

	/**
	 * @return bool
	 */
	public function getNoValidate(): bool;

	/**
	 * @param bool $val
	 */
	public function setNoValidate( bool $val ): void;

	/**
	 * @return string
	 */
	public function getTarget(): string;

	/**
	 * @param string $val
	 */
	public function setTarget( string $val ): void;

	/**
	 * @return HTMLFormControlsCollection
	 */
	public function getElements();

	/**
	 * @return int
	 */
	public function getLength(): int;

	/**
	 * @return void
	 */
	public function submit(): void;

	/**
	 * @param HTMLElement|null $submitter
	 * @return void
	 */
	public function requestSubmit( /* ?HTMLElement */ $submitter = null ): void;

	/**
	 * @return void
	 */
	public function reset(): void;

	/**
	 * @return bool
	 */
	public function checkValidity(): bool;

	/**
	 * @return bool
	 */
	public function reportValidity(): bool;

}

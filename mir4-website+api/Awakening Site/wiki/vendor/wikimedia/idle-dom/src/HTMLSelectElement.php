<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * HTMLSelectElement
 *
 * @see https://dom.spec.whatwg.org/#interface-htmlselectelement
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
 * @property string $autocomplete
 * @property bool $autofocus
 * @property bool $disabled
 * @property HTMLFormElement|null $form
 * @property bool $multiple
 * @property string $name
 * @property bool $required
 * @property int $size
 * @property string $type
 * @property HTMLOptionsCollection $options
 * @property int $length
 * @property HTMLCollection $selectedOptions
 * @property int $selectedIndex
 * @property string $value
 * @property bool $willValidate
 * @property ValidityState $validity
 * @property string $validationMessage
 * @property NodeList $labels
 * @phan-forbid-undeclared-magic-properties
 */
interface HTMLSelectElement extends HTMLElement, \ArrayAccess {
	// Direct parent: HTMLElement

	/**
	 * @return string
	 */
	public function getAutocomplete(): string;

	/**
	 * @param string $val
	 */
	public function setAutocomplete( string $val ): void;

	/**
	 * @return bool
	 */
	public function getAutofocus(): bool;

	/**
	 * @param bool $val
	 */
	public function setAutofocus( bool $val ): void;

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
	 * @return bool
	 */
	public function getMultiple(): bool;

	/**
	 * @param bool $val
	 */
	public function setMultiple( bool $val ): void;

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
	public function getRequired(): bool;

	/**
	 * @param bool $val
	 */
	public function setRequired( bool $val ): void;

	/**
	 * @return int
	 */
	public function getSize(): int;

	/**
	 * @param int $val
	 */
	public function setSize( int $val ): void;

	/**
	 * @return string
	 */
	public function getType(): string;

	/**
	 * @return HTMLOptionsCollection
	 */
	public function getOptions();

	/**
	 * @return int
	 */
	public function getLength(): int;

	/**
	 * @param int $val
	 */
	public function setLength( int $val ): void;

	/**
	 * @param int $index
	 * @return Element|null
	 */
	public function item( int $index );

	/**
	 * @param string $name
	 * @return HTMLOptionElement|null
	 */
	public function namedItem( string $name );

	/**
	 * @param HTMLOptionElement|HTMLOptGroupElement $element
	 * @param HTMLElement|int|null $before
	 * @return void
	 */
	public function add( /* mixed */ $element, /* ?mixed */ $before = null ): void;

	/**
	 * @param int $index
	 * @param HTMLOptionElement|null $option
	 * @return void
	 */
	public function setItem( int $index, /* ?HTMLOptionElement */ $option ): void;

	/**
	 * @return HTMLCollection
	 */
	public function getSelectedOptions();

	/**
	 * @return int
	 */
	public function getSelectedIndex(): int;

	/**
	 * @param int $val
	 */
	public function setSelectedIndex( int $val ): void;

	/**
	 * @return string
	 */
	public function getValue(): string;

	/**
	 * @param string $val
	 */
	public function setValue( string $val ): void;

	/**
	 * @return bool
	 */
	public function getWillValidate(): bool;

	/**
	 * @return ValidityState
	 */
	public function getValidity();

	/**
	 * @return string
	 */
	public function getValidationMessage(): string;

	/**
	 * @return bool
	 */
	public function checkValidity(): bool;

	/**
	 * @return bool
	 */
	public function reportValidity(): bool;

	/**
	 * @param string $error
	 * @return void
	 */
	public function setCustomValidity( string $error ): void;

	/**
	 * @return NodeList
	 */
	public function getLabels();

}

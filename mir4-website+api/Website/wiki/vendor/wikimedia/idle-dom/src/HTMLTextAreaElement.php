<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * HTMLTextAreaElement
 *
 * @see https://dom.spec.whatwg.org/#interface-htmltextareaelement
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
 * @property int $cols
 * @property string $dirName
 * @property bool $disabled
 * @property HTMLFormElement|null $form
 * @property int $maxLength
 * @property int $minLength
 * @property string $name
 * @property string $placeholder
 * @property bool $readOnly
 * @property bool $required
 * @property int $rows
 * @property string $wrap
 * @property string $type
 * @property string $defaultValue
 * @property string $value
 * @property int $textLength
 * @property bool $willValidate
 * @property ValidityState $validity
 * @property string $validationMessage
 * @property NodeList $labels
 * @property int $selectionStart
 * @property int $selectionEnd
 * @property string $selectionDirection
 * @phan-forbid-undeclared-magic-properties
 */
interface HTMLTextAreaElement extends HTMLElement {
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
	 * @return int
	 */
	public function getCols(): int;

	/**
	 * @param int $val
	 */
	public function setCols( int $val ): void;

	/**
	 * @return string
	 */
	public function getDirName(): string;

	/**
	 * @param string $val
	 */
	public function setDirName( string $val ): void;

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
	 * @return int
	 */
	public function getMaxLength(): int;

	/**
	 * @param int $val
	 */
	public function setMaxLength( int $val ): void;

	/**
	 * @return int
	 */
	public function getMinLength(): int;

	/**
	 * @param int $val
	 */
	public function setMinLength( int $val ): void;

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
	public function getPlaceholder(): string;

	/**
	 * @param string $val
	 */
	public function setPlaceholder( string $val ): void;

	/**
	 * @return bool
	 */
	public function getReadOnly(): bool;

	/**
	 * @param bool $val
	 */
	public function setReadOnly( bool $val ): void;

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
	public function getRows(): int;

	/**
	 * @param int $val
	 */
	public function setRows( int $val ): void;

	/**
	 * @return string
	 */
	public function getWrap(): string;

	/**
	 * @param string $val
	 */
	public function setWrap( string $val ): void;

	/**
	 * @return string
	 */
	public function getType(): string;

	/**
	 * @return string
	 */
	public function getDefaultValue(): string;

	/**
	 * @param string $val
	 */
	public function setDefaultValue( string $val ): void;

	/**
	 * @return string
	 */
	public function getValue(): string;

	/**
	 * @param ?string $val
	 */
	public function setValue( ?string $val ): void;

	/**
	 * @return int
	 */
	public function getTextLength(): int;

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

	/**
	 * @return void
	 */
	public function select(): void;

	/**
	 * @return int
	 */
	public function getSelectionStart(): int;

	/**
	 * @param int $val
	 */
	public function setSelectionStart( int $val ): void;

	/**
	 * @return int
	 */
	public function getSelectionEnd(): int;

	/**
	 * @param int $val
	 */
	public function setSelectionEnd( int $val ): void;

	/**
	 * @return string
	 */
	public function getSelectionDirection(): string;

	/**
	 * @param string $val
	 */
	public function setSelectionDirection( string $val ): void;

	/**
	 * @param string $replacement
	 * @return void
	 */
	public function setRangeText( string $replacement ): void;

	/**
	 * @param int $start
	 * @param int $end
	 * @param ?string $direction
	 * @return void
	 */
	public function setSelectionRange( int $start, int $end, ?string $direction = null ): void;

}

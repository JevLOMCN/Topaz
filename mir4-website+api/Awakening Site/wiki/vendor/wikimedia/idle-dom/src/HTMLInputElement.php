<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * HTMLInputElement
 *
 * @see https://dom.spec.whatwg.org/#interface-htmlinputelement
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
 * @property string $accept
 * @property string $alt
 * @property string $autocomplete
 * @property bool $autofocus
 * @property bool $defaultChecked
 * @property bool $checked
 * @property string $dirName
 * @property bool $disabled
 * @property HTMLFormElement|null $form
 * @property string $formEnctype
 * @property string $formMethod
 * @property bool $formNoValidate
 * @property string $formTarget
 * @property bool $indeterminate
 * @property HTMLElement|null $list
 * @property string $max
 * @property int $maxLength
 * @property string $min
 * @property int $minLength
 * @property bool $multiple
 * @property string $name
 * @property string $pattern
 * @property string $placeholder
 * @property bool $readOnly
 * @property bool $required
 * @property int $size
 * @property string $src
 * @property string $step
 * @property string $type
 * @property string $defaultValue
 * @property string $value
 * @property float $valueAsNumber
 * @property bool $willValidate
 * @property ValidityState $validity
 * @property string $validationMessage
 * @property NodeList|null $labels
 * @property ?int $selectionStart
 * @property ?int $selectionEnd
 * @property ?string $selectionDirection
 * @property string $align
 * @property string $useMap
 * @phan-forbid-undeclared-magic-properties
 */
interface HTMLInputElement extends HTMLElement {
	// Direct parent: HTMLElement

	/**
	 * @return string
	 */
	public function getAccept(): string;

	/**
	 * @param string $val
	 */
	public function setAccept( string $val ): void;

	/**
	 * @return string
	 */
	public function getAlt(): string;

	/**
	 * @param string $val
	 */
	public function setAlt( string $val ): void;

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
	public function getDefaultChecked(): bool;

	/**
	 * @param bool $val
	 */
	public function setDefaultChecked( bool $val ): void;

	/**
	 * @return bool
	 */
	public function getChecked(): bool;

	/**
	 * @param bool $val
	 */
	public function setChecked( bool $val ): void;

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
	 * @return string
	 */
	public function getFormEnctype(): string;

	/**
	 * @param string $val
	 */
	public function setFormEnctype( string $val ): void;

	/**
	 * @return string
	 */
	public function getFormMethod(): string;

	/**
	 * @param string $val
	 */
	public function setFormMethod( string $val ): void;

	/**
	 * @return bool
	 */
	public function getFormNoValidate(): bool;

	/**
	 * @param bool $val
	 */
	public function setFormNoValidate( bool $val ): void;

	/**
	 * @return string
	 */
	public function getFormTarget(): string;

	/**
	 * @param string $val
	 */
	public function setFormTarget( string $val ): void;

	/**
	 * @return bool
	 */
	public function getIndeterminate(): bool;

	/**
	 * @param bool $val
	 */
	public function setIndeterminate( bool $val ): void;

	/**
	 * @return HTMLElement|null
	 */
	public function getList();

	/**
	 * @return string
	 */
	public function getMax(): string;

	/**
	 * @param string $val
	 */
	public function setMax( string $val ): void;

	/**
	 * @return int
	 */
	public function getMaxLength(): int;

	/**
	 * @param int $val
	 */
	public function setMaxLength( int $val ): void;

	/**
	 * @return string
	 */
	public function getMin(): string;

	/**
	 * @param string $val
	 */
	public function setMin( string $val ): void;

	/**
	 * @return int
	 */
	public function getMinLength(): int;

	/**
	 * @param int $val
	 */
	public function setMinLength( int $val ): void;

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
	 * @return string
	 */
	public function getPattern(): string;

	/**
	 * @param string $val
	 */
	public function setPattern( string $val ): void;

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
	public function getSize(): int;

	/**
	 * @param int $val
	 */
	public function setSize( int $val ): void;

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
	public function getStep(): string;

	/**
	 * @param string $val
	 */
	public function setStep( string $val ): void;

	/**
	 * @return string
	 */
	public function getType(): string;

	/**
	 * @param string $val
	 */
	public function setType( string $val ): void;

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
	 * @return float
	 */
	public function getValueAsNumber(): float;

	/**
	 * @param float $val
	 */
	public function setValueAsNumber( float $val ): void;

	/**
	 * @param int $n
	 * @return void
	 */
	public function stepUp( int $n = 1 ): void;

	/**
	 * @param int $n
	 * @return void
	 */
	public function stepDown( int $n = 1 ): void;

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
	 * @return NodeList|null
	 */
	public function getLabels();

	/**
	 * @return void
	 */
	public function select(): void;

	/**
	 * @return ?int
	 */
	public function getSelectionStart(): ?int;

	/**
	 * @param ?int $val
	 */
	public function setSelectionStart( ?int $val ): void;

	/**
	 * @return ?int
	 */
	public function getSelectionEnd(): ?int;

	/**
	 * @param ?int $val
	 */
	public function setSelectionEnd( ?int $val ): void;

	/**
	 * @return ?string
	 */
	public function getSelectionDirection(): ?string;

	/**
	 * @param ?string $val
	 */
	public function setSelectionDirection( ?string $val ): void;

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
	public function getUseMap(): string;

	/**
	 * @param string $val
	 */
	public function setUseMap( string $val ): void;

}

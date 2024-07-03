<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * HTMLObjectElement
 *
 * @see https://dom.spec.whatwg.org/#interface-htmlobjectelement
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
 * @property string $data
 * @property string $type
 * @property string $name
 * @property string $useMap
 * @property HTMLFormElement|null $form
 * @property string $width
 * @property string $height
 * @property Document|null $contentDocument
 * @property bool $willValidate
 * @property ValidityState $validity
 * @property string $validationMessage
 * @property string $align
 * @property string $archive
 * @property string $code
 * @property bool $declare
 * @property int $hspace
 * @property string $standby
 * @property int $vspace
 * @property string $codeBase
 * @property string $codeType
 * @property string $border
 * @phan-forbid-undeclared-magic-properties
 */
interface HTMLObjectElement extends HTMLElement {
	// Direct parent: HTMLElement

	/**
	 * @return string
	 */
	public function getData(): string;

	/**
	 * @param string $val
	 */
	public function setData( string $val ): void;

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
	public function getName(): string;

	/**
	 * @param string $val
	 */
	public function setName( string $val ): void;

	/**
	 * @return string
	 */
	public function getUseMap(): string;

	/**
	 * @param string $val
	 */
	public function setUseMap( string $val ): void;

	/**
	 * @return HTMLFormElement|null
	 */
	public function getForm();

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
	 * @return Document|null
	 */
	public function getContentDocument();

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
	public function getArchive(): string;

	/**
	 * @param string $val
	 */
	public function setArchive( string $val ): void;

	/**
	 * @return string
	 */
	public function getCode(): string;

	/**
	 * @param string $val
	 */
	public function setCode( string $val ): void;

	/**
	 * @return bool
	 */
	public function getDeclare(): bool;

	/**
	 * @param bool $val
	 */
	public function setDeclare( bool $val ): void;

	/**
	 * @return int
	 */
	public function getHspace(): int;

	/**
	 * @param int $val
	 */
	public function setHspace( int $val ): void;

	/**
	 * @return string
	 */
	public function getStandby(): string;

	/**
	 * @param string $val
	 */
	public function setStandby( string $val ): void;

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
	public function getCodeBase(): string;

	/**
	 * @param string $val
	 */
	public function setCodeBase( string $val ): void;

	/**
	 * @return string
	 */
	public function getCodeType(): string;

	/**
	 * @param string $val
	 */
	public function setCodeType( string $val ): void;

	/**
	 * @return string
	 */
	public function getBorder(): string;

	/**
	 * @param ?string $val
	 */
	public function setBorder( ?string $val ): void;

}

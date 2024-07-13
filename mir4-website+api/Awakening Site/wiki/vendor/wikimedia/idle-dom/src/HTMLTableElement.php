<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * HTMLTableElement
 *
 * @see https://dom.spec.whatwg.org/#interface-htmltableelement
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
 * @property HTMLTableCaptionElement|null $caption
 * @property HTMLTableSectionElement|null $tHead
 * @property HTMLTableSectionElement|null $tFoot
 * @property HTMLCollection $tBodies
 * @property HTMLCollection $rows
 * @property string $align
 * @property string $border
 * @property string $frame
 * @property string $rules
 * @property string $summary
 * @property string $width
 * @property string $bgColor
 * @property string $cellPadding
 * @property string $cellSpacing
 * @phan-forbid-undeclared-magic-properties
 */
interface HTMLTableElement extends HTMLElement {
	// Direct parent: HTMLElement

	/**
	 * @return HTMLTableCaptionElement|null
	 */
	public function getCaption();

	/**
	 * @param HTMLTableCaptionElement|null $val
	 */
	public function setCaption( /* ?HTMLTableCaptionElement */ $val ): void;

	/**
	 * @return HTMLTableCaptionElement
	 */
	public function createCaption();

	/**
	 * @return void
	 */
	public function deleteCaption(): void;

	/**
	 * @return HTMLTableSectionElement|null
	 */
	public function getTHead();

	/**
	 * @param HTMLTableSectionElement|null $val
	 */
	public function setTHead( /* ?HTMLTableSectionElement */ $val ): void;

	/**
	 * @return HTMLTableSectionElement
	 */
	public function createTHead();

	/**
	 * @return void
	 */
	public function deleteTHead(): void;

	/**
	 * @return HTMLTableSectionElement|null
	 */
	public function getTFoot();

	/**
	 * @param HTMLTableSectionElement|null $val
	 */
	public function setTFoot( /* ?HTMLTableSectionElement */ $val ): void;

	/**
	 * @return HTMLTableSectionElement
	 */
	public function createTFoot();

	/**
	 * @return void
	 */
	public function deleteTFoot(): void;

	/**
	 * @return HTMLCollection
	 */
	public function getTBodies();

	/**
	 * @return HTMLTableSectionElement
	 */
	public function createTBody();

	/**
	 * @return HTMLCollection
	 */
	public function getRows();

	/**
	 * @param int $index
	 * @return HTMLTableRowElement
	 */
	public function insertRow( int $index = -1 );

	/**
	 * @param int $index
	 * @return void
	 */
	public function deleteRow( int $index ): void;

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
	public function getBorder(): string;

	/**
	 * @param string $val
	 */
	public function setBorder( string $val ): void;

	/**
	 * @return string
	 */
	public function getFrame(): string;

	/**
	 * @param string $val
	 */
	public function setFrame( string $val ): void;

	/**
	 * @return string
	 */
	public function getRules(): string;

	/**
	 * @param string $val
	 */
	public function setRules( string $val ): void;

	/**
	 * @return string
	 */
	public function getSummary(): string;

	/**
	 * @param string $val
	 */
	public function setSummary( string $val ): void;

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
	public function getBgColor(): string;

	/**
	 * @param ?string $val
	 */
	public function setBgColor( ?string $val ): void;

	/**
	 * @return string
	 */
	public function getCellPadding(): string;

	/**
	 * @param ?string $val
	 */
	public function setCellPadding( ?string $val ): void;

	/**
	 * @return string
	 */
	public function getCellSpacing(): string;

	/**
	 * @param ?string $val
	 */
	public function setCellSpacing( ?string $val ): void;

}

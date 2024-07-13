<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * HTMLImageElement
 *
 * @see https://dom.spec.whatwg.org/#interface-htmlimageelement
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
 * @property string $referrerPolicy
 * @property string $alt
 * @property string $src
 * @property string $srcset
 * @property string $sizes
 * @property string $useMap
 * @property bool $isMap
 * @property int $width
 * @property int $height
 * @property int $naturalWidth
 * @property int $naturalHeight
 * @property bool $complete
 * @property string $currentSrc
 * @property string $decoding
 * @property string $loading
 * @property string $name
 * @property string $lowsrc
 * @property string $align
 * @property int $hspace
 * @property int $vspace
 * @property string $longDesc
 * @property string $border
 * @phan-forbid-undeclared-magic-properties
 */
interface HTMLImageElement extends HTMLElement, CrossOrigin, ReferrerPolicy {
	// Direct parent: HTMLElement

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
	public function getSrc(): string;

	/**
	 * @param string $val
	 */
	public function setSrc( string $val ): void;

	/**
	 * @return string
	 */
	public function getSrcset(): string;

	/**
	 * @param string $val
	 */
	public function setSrcset( string $val ): void;

	/**
	 * @return string
	 */
	public function getSizes(): string;

	/**
	 * @param string $val
	 */
	public function setSizes( string $val ): void;

	/**
	 * @return string
	 */
	public function getUseMap(): string;

	/**
	 * @param string $val
	 */
	public function setUseMap( string $val ): void;

	/**
	 * @return bool
	 */
	public function getIsMap(): bool;

	/**
	 * @param bool $val
	 */
	public function setIsMap( bool $val ): void;

	/**
	 * @return int
	 */
	public function getWidth(): int;

	/**
	 * @param int $val
	 */
	public function setWidth( int $val ): void;

	/**
	 * @return int
	 */
	public function getHeight(): int;

	/**
	 * @param int $val
	 */
	public function setHeight( int $val ): void;

	/**
	 * @return int
	 */
	public function getNaturalWidth(): int;

	/**
	 * @return int
	 */
	public function getNaturalHeight(): int;

	/**
	 * @return bool
	 */
	public function getComplete(): bool;

	/**
	 * @return string
	 */
	public function getCurrentSrc(): string;

	/**
	 * @return string
	 */
	public function getDecoding(): string;

	/**
	 * @param string $val
	 */
	public function setDecoding( string $val ): void;

	/**
	 * @return string
	 */
	public function getLoading(): string;

	/**
	 * @param string $val
	 */
	public function setLoading( string $val ): void;

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
	public function getLowsrc(): string;

	/**
	 * @param string $val
	 */
	public function setLowsrc( string $val ): void;

	/**
	 * @return string
	 */
	public function getAlign(): string;

	/**
	 * @param string $val
	 */
	public function setAlign( string $val ): void;

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
	public function getVspace(): int;

	/**
	 * @param int $val
	 */
	public function setVspace( int $val ): void;

	/**
	 * @return string
	 */
	public function getLongDesc(): string;

	/**
	 * @param string $val
	 */
	public function setLongDesc( string $val ): void;

	/**
	 * @return string
	 */
	public function getBorder(): string;

	/**
	 * @param ?string $val
	 */
	public function setBorder( ?string $val ): void;

}

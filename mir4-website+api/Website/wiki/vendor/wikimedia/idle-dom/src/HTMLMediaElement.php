<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * HTMLMediaElement
 *
 * @see https://dom.spec.whatwg.org/#interface-htmlmediaelement
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
 * @property string $src
 * @property string $currentSrc
 * @property int $networkState
 * @property string $preload
 * @property TimeRanges $buffered
 * @property int $readyState
 * @property bool $seeking
 * @property float $currentTime
 * @property float $duration
 * @property bool $paused
 * @property float $defaultPlaybackRate
 * @property float $playbackRate
 * @property TimeRanges $played
 * @property TimeRanges $seekable
 * @property bool $ended
 * @property bool $autoplay
 * @property bool $loop
 * @property bool $controls
 * @property float $volume
 * @property bool $muted
 * @property bool $defaultMuted
 * @property AudioTrackList $audioTracks
 * @property VideoTrackList $videoTracks
 * @property TextTrackList $textTracks
 * @phan-forbid-undeclared-magic-properties
 */
interface HTMLMediaElement extends HTMLElement, CrossOrigin {
	// Direct parent: HTMLElement

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
	public function getCurrentSrc(): string;

	/** @var int */
	public const NETWORK_EMPTY = 0;

	/** @var int */
	public const NETWORK_IDLE = 1;

	/** @var int */
	public const NETWORK_LOADING = 2;

	/** @var int */
	public const NETWORK_NO_SOURCE = 3;

	/**
	 * @return int
	 */
	public function getNetworkState(): int;

	/**
	 * @return string
	 */
	public function getPreload(): string;

	/**
	 * @param string $val
	 */
	public function setPreload( string $val ): void;

	/**
	 * @return TimeRanges
	 */
	public function getBuffered();

	/**
	 * @return void
	 */
	public function load(): void;

	/** @var int */
	public const HAVE_NOTHING = 0;

	/** @var int */
	public const HAVE_METADATA = 1;

	/** @var int */
	public const HAVE_CURRENT_DATA = 2;

	/** @var int */
	public const HAVE_FUTURE_DATA = 3;

	/** @var int */
	public const HAVE_ENOUGH_DATA = 4;

	/**
	 * @return int
	 */
	public function getReadyState(): int;

	/**
	 * @return bool
	 */
	public function getSeeking(): bool;

	/**
	 * @return float
	 */
	public function getCurrentTime(): float;

	/**
	 * @param float $val
	 */
	public function setCurrentTime( float $val ): void;

	/**
	 * @return float
	 */
	public function getDuration(): float;

	/**
	 * @return bool
	 */
	public function getPaused(): bool;

	/**
	 * @return float
	 */
	public function getDefaultPlaybackRate(): float;

	/**
	 * @param float $val
	 */
	public function setDefaultPlaybackRate( float $val ): void;

	/**
	 * @return float
	 */
	public function getPlaybackRate(): float;

	/**
	 * @param float $val
	 */
	public function setPlaybackRate( float $val ): void;

	/**
	 * @return TimeRanges
	 */
	public function getPlayed();

	/**
	 * @return TimeRanges
	 */
	public function getSeekable();

	/**
	 * @return bool
	 */
	public function getEnded(): bool;

	/**
	 * @return bool
	 */
	public function getAutoplay(): bool;

	/**
	 * @param bool $val
	 */
	public function setAutoplay( bool $val ): void;

	/**
	 * @return bool
	 */
	public function getLoop(): bool;

	/**
	 * @param bool $val
	 */
	public function setLoop( bool $val ): void;

	/**
	 * @return void
	 */
	public function pause(): void;

	/**
	 * @return bool
	 */
	public function getControls(): bool;

	/**
	 * @param bool $val
	 */
	public function setControls( bool $val ): void;

	/**
	 * @return float
	 */
	public function getVolume(): float;

	/**
	 * @param float $val
	 */
	public function setVolume( float $val ): void;

	/**
	 * @return bool
	 */
	public function getMuted(): bool;

	/**
	 * @param bool $val
	 */
	public function setMuted( bool $val ): void;

	/**
	 * @return bool
	 */
	public function getDefaultMuted(): bool;

	/**
	 * @param bool $val
	 */
	public function setDefaultMuted( bool $val ): void;

	/**
	 * @return AudioTrackList
	 */
	public function getAudioTracks();

	/**
	 * @return VideoTrackList
	 */
	public function getVideoTracks();

	/**
	 * @return TextTrackList
	 */
	public function getTextTracks();

}

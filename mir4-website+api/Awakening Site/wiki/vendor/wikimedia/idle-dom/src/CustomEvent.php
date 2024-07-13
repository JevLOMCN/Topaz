<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * CustomEvent
 *
 * @see https://dom.spec.whatwg.org/#interface-customevent
 *
 * @property string $type
 * @property EventTarget|null $target
 * @property EventTarget|null $srcElement
 * @property EventTarget|null $currentTarget
 * @property int $eventPhase
 * @property bool $cancelBubble
 * @property bool $bubbles
 * @property bool $cancelable
 * @property bool $returnValue
 * @property bool $defaultPrevented
 * @property bool $composed
 * @property bool $isTrusted
 * @property float $timeStamp
 * @property mixed|null $detail
 * @phan-forbid-undeclared-magic-properties
 */
interface CustomEvent extends Event {
	// Direct parent: Event

	/**
	 * @return mixed|null
	 */
	public function getDetail();

	/**
	 * @param string $type
	 * @param bool $bubbles
	 * @param bool $cancelable
	 * @param mixed|null $detail
	 * @return void
	 */
	public function initCustomEvent( string $type, bool $bubbles = false, bool $cancelable = false, /* any */ $detail = null ): void;

}

<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * EventInit
 *
 * @see https://dom.spec.whatwg.org/#dictdef-eventinit
 *
 * @property bool $bubbles
 * @property bool $cancelable
 * @property bool $composed
 * @phan-forbid-undeclared-magic-properties
 */
abstract class EventInit implements \ArrayAccess {
	// Dictionary type

	use \Wikimedia\IDLeDOM\Helper\EventInit;

	/**
	 * @return bool
	 */
	abstract public function getBubbles(): bool;

	/**
	 * @return bool
	 */
	abstract public function getCancelable(): bool;

	/**
	 * @return bool
	 */
	abstract public function getComposed(): bool;

}

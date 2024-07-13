<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * EventListenerOptions
 *
 * @see https://dom.spec.whatwg.org/#dictdef-eventlisteneroptions
 *
 * @property bool $capture
 * @phan-forbid-undeclared-magic-properties
 */
abstract class EventListenerOptions implements \ArrayAccess {
	// Dictionary type

	use \Wikimedia\IDLeDOM\Helper\EventListenerOptions;

	/**
	 * @return bool
	 */
	abstract public function getCapture(): bool;

}

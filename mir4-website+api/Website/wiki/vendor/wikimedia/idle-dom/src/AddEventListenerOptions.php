<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * AddEventListenerOptions
 *
 * @see https://dom.spec.whatwg.org/#dictdef-addeventlisteneroptions
 *
 * @property bool $capture
 * @property bool $passive
 * @property bool $once
 * @property AbortSignal $signal
 * @phan-forbid-undeclared-magic-properties
 */
abstract class AddEventListenerOptions extends EventListenerOptions {
	// Dictionary type
	// Direct parent: EventListenerOptions

	use \Wikimedia\IDLeDOM\Helper\AddEventListenerOptions;

	/**
	 * @return bool
	 */
	abstract public function getPassive(): bool;

	/**
	 * @return bool
	 */
	abstract public function getOnce(): bool;

	/**
	 * @return AbortSignal
	 */
	abstract public function getSignal();

}

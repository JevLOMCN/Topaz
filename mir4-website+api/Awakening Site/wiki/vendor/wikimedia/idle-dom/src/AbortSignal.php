<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * AbortSignal
 *
 * @see https://dom.spec.whatwg.org/#interface-abortsignal
 *
 * @property bool $aborted
 * @property EventHandlerNonNull|callable|null $onabort
 * @phan-forbid-undeclared-magic-properties
 */
interface AbortSignal extends EventTarget {
	// Direct parent: EventTarget

	/**
	 * @return bool
	 */
	public function getAborted(): bool;

	/**
	 * @return EventHandlerNonNull|callable|null
	 */
	public function getOnabort();

	/**
	 * @param EventHandlerNonNull|callable|null $val
	 */
	public function setOnabort( /* ?mixed */ $val ): void;

}

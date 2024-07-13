<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * AbortController
 *
 * @see https://dom.spec.whatwg.org/#interface-abortcontroller
 *
 * @property AbortSignal $signal
 * @phan-forbid-undeclared-magic-properties
 */
interface AbortController {

	/**
	 * @return AbortSignal
	 */
	public function getSignal();

	/**
	 * @return void
	 */
	public function abort(): void;

}

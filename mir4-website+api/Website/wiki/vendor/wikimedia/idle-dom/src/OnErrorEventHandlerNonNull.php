<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * OnErrorEventHandlerNonNull
 *
 * @see https://dom.spec.whatwg.org/#callbackdef-onerroreventhandlernonnull
 *
 * @phan-forbid-undeclared-magic-properties
 */
interface OnErrorEventHandlerNonNull {
	/**
	 * @param Event|string $event
	 * @param ?string $source
	 * @param ?int $lineno
	 * @param ?int $colno
	 * @param mixed|null $error
	 * @return mixed|null
	 */
	public function invoke( /* mixed */ $event, ?string $source = null, ?int $lineno = null, ?int $colno = null, /* any */ $error = null );
}

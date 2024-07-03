<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * OnBeforeUnloadEventHandlerNonNull
 *
 * @see https://dom.spec.whatwg.org/#callbackdef-onbeforeunloadeventhandlernonnull
 *
 * @phan-forbid-undeclared-magic-properties
 */
interface OnBeforeUnloadEventHandlerNonNull {
	/**
	 * @param Event $event
	 * @return ?string
	 */
	public function invoke( /* Event */ $event ): ?string;
}

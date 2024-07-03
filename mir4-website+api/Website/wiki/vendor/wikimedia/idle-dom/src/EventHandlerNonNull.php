<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * EventHandlerNonNull
 *
 * @see https://dom.spec.whatwg.org/#callbackdef-eventhandlernonnull
 *
 * @phan-forbid-undeclared-magic-properties
 */
interface EventHandlerNonNull {
	/**
	 * @param Event $event
	 * @return mixed|null
	 */
	public function invoke( /* Event */ $event );
}

<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * EventListener
 *
 * @see https://dom.spec.whatwg.org/#callbackdef-eventlistener
 *
 * @phan-forbid-undeclared-magic-properties
 */
interface EventListener {
	/**
	 * @param Event $event
	 * @return void
	 */
	public function handleEvent( /* Event */ $event ): void;

}

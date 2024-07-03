<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * EventTarget
 *
 * @see https://dom.spec.whatwg.org/#interface-eventtarget
 *
 * @phan-forbid-undeclared-magic-properties
 */
interface EventTarget {

	/**
	 * @param string $type
	 * @param EventListener|callable|null $callback
	 * @param AddEventListenerOptions|associative-array|bool|null $options
	 * @return void
	 */
	public function addEventListener( string $type, /* ?mixed */ $callback, /* ?mixed */ $options = null ): void;

	/**
	 * @param string $type
	 * @param EventListener|callable|null $callback
	 * @param EventListenerOptions|associative-array|bool|null $options
	 * @return void
	 */
	public function removeEventListener( string $type, /* ?mixed */ $callback, /* ?mixed */ $options = null ): void;

	/**
	 * @param Event $event
	 * @return bool
	 */
	public function dispatchEvent( /* Event */ $event ): bool;

}

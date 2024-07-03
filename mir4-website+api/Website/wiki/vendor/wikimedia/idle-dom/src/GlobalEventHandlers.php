<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * GlobalEventHandlers
 *
 * @see https://dom.spec.whatwg.org/#interface-globaleventhandlers
 *
 * @property EventHandlerNonNull|callable|null $onload
 * @phan-forbid-undeclared-magic-properties
 */
interface GlobalEventHandlers {
	/**
	 * @return EventHandlerNonNull|callable|null
	 */
	public function getOnload();

	/**
	 * @param EventHandlerNonNull|callable|null $val
	 */
	public function setOnload( /* ?mixed */ $val ): void;

}

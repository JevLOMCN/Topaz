<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * CustomEventInit
 *
 * @see https://dom.spec.whatwg.org/#dictdef-customeventinit
 *
 * @property bool $bubbles
 * @property bool $cancelable
 * @property bool $composed
 * @property mixed|null $detail
 * @phan-forbid-undeclared-magic-properties
 */
abstract class CustomEventInit extends EventInit {
	// Dictionary type
	// Direct parent: EventInit

	use \Wikimedia\IDLeDOM\Helper\CustomEventInit;

	/**
	 * @return mixed|null
	 */
	abstract public function getDetail();

}

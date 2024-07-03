<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * ShadowRootInit
 *
 * @see https://dom.spec.whatwg.org/#dictdef-shadowrootinit
 *
 * @property string $mode
 * @property bool $delegatesFocus
 * @phan-forbid-undeclared-magic-properties
 */
abstract class ShadowRootInit implements \ArrayAccess {
	// Dictionary type

	use \Wikimedia\IDLeDOM\Helper\ShadowRootInit;

	/**
	 * @return string
	 */
	abstract public function getMode(): /* ShadowRootMode */ string;

	/**
	 * @return bool
	 */
	abstract public function getDelegatesFocus(): bool;

}

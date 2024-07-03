<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * ReferrerPolicy
 *
 * @see https://dom.spec.whatwg.org/#interface-referrerpolicy
 *
 * @property string $referrerPolicy
 * @phan-forbid-undeclared-magic-properties
 */
interface ReferrerPolicy {
	/**
	 * @return string
	 */
	public function getReferrerPolicy(): string;

	/**
	 * @param string $val
	 */
	public function setReferrerPolicy( string $val ): void;

}

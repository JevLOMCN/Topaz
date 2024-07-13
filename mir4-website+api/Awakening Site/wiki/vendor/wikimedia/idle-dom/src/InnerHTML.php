<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * InnerHTML
 *
 * @see https://dom.spec.whatwg.org/#interface-innerhtml
 *
 * @property string $innerHTML
 * @phan-forbid-undeclared-magic-properties
 */
interface InnerHTML {
	/**
	 * @return string
	 */
	public function getInnerHTML(): string;

	/**
	 * @param ?string $val
	 */
	public function setInnerHTML( ?string $val ): void;

}

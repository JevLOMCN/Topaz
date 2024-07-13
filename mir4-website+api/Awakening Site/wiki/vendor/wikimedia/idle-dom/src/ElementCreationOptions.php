<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * ElementCreationOptions
 *
 * @see https://dom.spec.whatwg.org/#dictdef-elementcreationoptions
 *
 * @property string $is
 * @phan-forbid-undeclared-magic-properties
 */
abstract class ElementCreationOptions implements \ArrayAccess {
	// Dictionary type

	use \Wikimedia\IDLeDOM\Helper\ElementCreationOptions;

	/**
	 * @return string
	 */
	abstract public function getIs(): string;

}

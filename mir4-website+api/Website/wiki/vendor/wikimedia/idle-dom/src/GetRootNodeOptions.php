<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * GetRootNodeOptions
 *
 * @see https://dom.spec.whatwg.org/#dictdef-getrootnodeoptions
 *
 * @property bool $composed
 * @phan-forbid-undeclared-magic-properties
 */
abstract class GetRootNodeOptions implements \ArrayAccess {
	// Dictionary type

	use \Wikimedia\IDLeDOM\Helper\GetRootNodeOptions;

	/**
	 * @return bool
	 */
	abstract public function getComposed(): bool;

}

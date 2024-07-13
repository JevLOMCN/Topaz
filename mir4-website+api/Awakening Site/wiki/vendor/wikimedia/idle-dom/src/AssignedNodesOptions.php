<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * AssignedNodesOptions
 *
 * @see https://dom.spec.whatwg.org/#dictdef-assignednodesoptions
 *
 * @property bool $flatten
 * @phan-forbid-undeclared-magic-properties
 */
abstract class AssignedNodesOptions implements \ArrayAccess {
	// Dictionary type

	use \Wikimedia\IDLeDOM\Helper\AssignedNodesOptions;

	/**
	 * @return bool
	 */
	abstract public function getFlatten(): bool;

}

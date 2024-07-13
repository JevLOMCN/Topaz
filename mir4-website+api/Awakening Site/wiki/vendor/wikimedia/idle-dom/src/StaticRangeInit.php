<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * StaticRangeInit
 *
 * @see https://dom.spec.whatwg.org/#dictdef-staticrangeinit
 *
 * @property Node $startContainer
 * @property int $startOffset
 * @property Node $endContainer
 * @property int $endOffset
 * @phan-forbid-undeclared-magic-properties
 */
abstract class StaticRangeInit implements \ArrayAccess {
	// Dictionary type

	use \Wikimedia\IDLeDOM\Helper\StaticRangeInit;

	/**
	 * @return Node
	 */
	abstract public function getStartContainer();

	/**
	 * @return int
	 */
	abstract public function getStartOffset(): int;

	/**
	 * @return Node
	 */
	abstract public function getEndContainer();

	/**
	 * @return int
	 */
	abstract public function getEndOffset(): int;

}

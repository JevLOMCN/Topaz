<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * AbstractRange
 *
 * @see https://dom.spec.whatwg.org/#interface-abstractrange
 *
 * @property Node $startContainer
 * @property int $startOffset
 * @property Node $endContainer
 * @property int $endOffset
 * @property bool $collapsed
 * @phan-forbid-undeclared-magic-properties
 */
interface AbstractRange {
	/**
	 * @return Node
	 */
	public function getStartContainer();

	/**
	 * @return int
	 */
	public function getStartOffset(): int;

	/**
	 * @return Node
	 */
	public function getEndContainer();

	/**
	 * @return int
	 */
	public function getEndOffset(): int;

	/**
	 * @return bool
	 */
	public function getCollapsed(): bool;

}

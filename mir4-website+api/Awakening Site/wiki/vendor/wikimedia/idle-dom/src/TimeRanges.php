<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * TimeRanges
 *
 * @see https://dom.spec.whatwg.org/#interface-timeranges
 *
 * @property int $length
 * @phan-forbid-undeclared-magic-properties
 */
interface TimeRanges {
	/**
	 * @return int
	 */
	public function getLength(): int;

	/**
	 * @param int $index
	 * @return float
	 */
	public function start( int $index ): float;

	/**
	 * @param int $index
	 * @return float
	 */
	public function end( int $index ): float;

}

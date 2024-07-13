<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * StyleSheetList
 *
 * @see https://dom.spec.whatwg.org/#interface-stylesheetlist
 *
 * @property int $length
 * @phan-forbid-undeclared-magic-properties
 */
interface StyleSheetList extends \ArrayAccess {
	/**
	 * @param int $index
	 * @return CSSStyleSheet|null
	 */
	public function item( int $index );

	/**
	 * @return int
	 */
	public function getLength(): int;

}

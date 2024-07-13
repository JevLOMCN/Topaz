<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * CSSRuleList
 *
 * @see https://dom.spec.whatwg.org/#interface-cssrulelist
 *
 * @property int $length
 * @phan-forbid-undeclared-magic-properties
 */
interface CSSRuleList extends \ArrayAccess {
	/**
	 * @param int $index
	 * @return CSSRule|null
	 */
	public function item( int $index );

	/**
	 * @return int
	 */
	public function getLength(): int;

}

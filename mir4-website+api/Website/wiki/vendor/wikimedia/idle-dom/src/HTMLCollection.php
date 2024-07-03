<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * HTMLCollection
 *
 * @see https://dom.spec.whatwg.org/#interface-htmlcollection
 *
 * @property int $length
 * @phan-forbid-undeclared-magic-properties
 */
interface HTMLCollection extends \ArrayAccess, \IteratorAggregate, \Countable {
	/**
	 * @return int
	 */
	public function getLength(): int;

	/**
	 * @param int $index
	 * @return Element|null
	 */
	public function item( int $index );

	/**
	 * @param string $name
	 * @return Element|null
	 */
	public function namedItem( string $name );

	/**
	 * @return \Iterator<Element> Value iterator returning Element items
	 */
	public function getIterator();

}

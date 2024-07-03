<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * HTMLOptionsCollection
 *
 * @see https://dom.spec.whatwg.org/#interface-htmloptionscollection
 *
 * @property int $length
 * @property int $selectedIndex
 * @phan-forbid-undeclared-magic-properties
 */
interface HTMLOptionsCollection extends HTMLCollection {
	// Direct parent: HTMLCollection

	/**
	 * @param int $index
	 * @param HTMLOptionElement|null $option
	 * @return void
	 */
	public function setItem( int $index, /* ?HTMLOptionElement */ $option ): void;

	/**
	 * @param HTMLOptionElement|HTMLOptGroupElement $element
	 * @param HTMLElement|int|null $before
	 * @return void
	 */
	public function add( /* mixed */ $element, /* ?mixed */ $before = null ): void;

	/**
	 * @param int $index
	 * @return void
	 */
	public function remove( int $index ): void;

	/**
	 * @return int
	 */
	public function getSelectedIndex(): int;

	/**
	 * @param int $val
	 */
	public function setSelectedIndex( int $val ): void;

}

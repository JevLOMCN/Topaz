<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * ParentNode
 *
 * @see https://dom.spec.whatwg.org/#interface-parentnode
 *
 * @property HTMLCollection $children
 * @property Element|null $firstElementChild
 * @property Element|null $lastElementChild
 * @property int $childElementCount
 * @phan-forbid-undeclared-magic-properties
 */
interface ParentNode {
	/**
	 * @return HTMLCollection
	 */
	public function getChildren();

	/**
	 * @return Element|null
	 */
	public function getFirstElementChild();

	/**
	 * @return Element|null
	 */
	public function getLastElementChild();

	/**
	 * @return int
	 */
	public function getChildElementCount(): int;

	/**
	 * @param Node|string ...$nodes
	 * @return void
	 */
	public function prepend( /* mixed */ ...$nodes ): void;

	/**
	 * @param Node|string ...$nodes
	 * @return void
	 */
	public function append( /* mixed */ ...$nodes ): void;

	/**
	 * @param Node|string ...$nodes
	 * @return void
	 */
	public function replaceChildren( /* mixed */ ...$nodes ): void;

	/**
	 * @param string $selectors
	 * @return Element|null
	 */
	public function querySelector( string $selectors );

	/**
	 * @param string $selectors
	 * @return NodeList
	 */
	public function querySelectorAll( string $selectors );

}

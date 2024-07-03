<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * TreeWalker
 *
 * @see https://dom.spec.whatwg.org/#interface-treewalker
 *
 * @property Node $root
 * @property int $whatToShow
 * @property NodeFilter|callable|null $filter
 * @property Node $currentNode
 * @phan-forbid-undeclared-magic-properties
 */
interface TreeWalker {
	/**
	 * @return Node
	 */
	public function getRoot();

	/**
	 * @return int
	 */
	public function getWhatToShow(): int;

	/**
	 * @return NodeFilter|callable|null
	 */
	public function getFilter();

	/**
	 * @return Node
	 */
	public function getCurrentNode();

	/**
	 * @param Node $val
	 */
	public function setCurrentNode( /* Node */ $val ): void;

	/**
	 * @return Node|null
	 */
	public function parentNode();

	/**
	 * @return Node|null
	 */
	public function firstChild();

	/**
	 * @return Node|null
	 */
	public function lastChild();

	/**
	 * @return Node|null
	 */
	public function previousSibling();

	/**
	 * @return Node|null
	 */
	public function nextSibling();

	/**
	 * @return Node|null
	 */
	public function previousNode();

	/**
	 * @return Node|null
	 */
	public function nextNode();

}

<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * NodeIterator
 *
 * @see https://dom.spec.whatwg.org/#interface-nodeiterator
 *
 * @property Node $root
 * @property Node $referenceNode
 * @property bool $pointerBeforeReferenceNode
 * @property int $whatToShow
 * @property NodeFilter|callable|null $filter
 * @phan-forbid-undeclared-magic-properties
 */
interface NodeIterator {
	/**
	 * @return Node
	 */
	public function getRoot();

	/**
	 * @return Node
	 */
	public function getReferenceNode();

	/**
	 * @return bool
	 */
	public function getPointerBeforeReferenceNode(): bool;

	/**
	 * @return int
	 */
	public function getWhatToShow(): int;

	/**
	 * @return NodeFilter|callable|null
	 */
	public function getFilter();

	/**
	 * @return Node|null
	 */
	public function nextNode();

	/**
	 * @return Node|null
	 */
	public function previousNode();

	/**
	 * @return void
	 */
	public function detach(): void;

}

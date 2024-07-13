<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * MutationRecord
 *
 * @see https://dom.spec.whatwg.org/#interface-mutationrecord
 *
 * @property string $type
 * @property Node $target
 * @property NodeList $addedNodes
 * @property NodeList $removedNodes
 * @property Node|null $previousSibling
 * @property Node|null $nextSibling
 * @property ?string $attributeName
 * @property ?string $attributeNamespace
 * @property ?string $oldValue
 * @phan-forbid-undeclared-magic-properties
 */
interface MutationRecord {
	/**
	 * @return string
	 */
	public function getType(): string;

	/**
	 * @return Node
	 */
	public function getTarget();

	/**
	 * @return NodeList
	 */
	public function getAddedNodes();

	/**
	 * @return NodeList
	 */
	public function getRemovedNodes();

	/**
	 * @return Node|null
	 */
	public function getPreviousSibling();

	/**
	 * @return Node|null
	 */
	public function getNextSibling();

	/**
	 * @return ?string
	 */
	public function getAttributeName(): ?string;

	/**
	 * @return ?string
	 */
	public function getAttributeNamespace(): ?string;

	/**
	 * @return ?string
	 */
	public function getOldValue(): ?string;

}

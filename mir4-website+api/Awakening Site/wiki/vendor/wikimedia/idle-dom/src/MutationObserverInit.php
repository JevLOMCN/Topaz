<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * MutationObserverInit
 *
 * @see https://dom.spec.whatwg.org/#dictdef-mutationobserverinit
 *
 * @property bool $childList
 * @property bool $attributes
 * @property bool $characterData
 * @property bool $subtree
 * @property bool $attributeOldValue
 * @property bool $characterDataOldValue
 * @property list<string> $attributeFilter
 * @phan-forbid-undeclared-magic-properties
 */
abstract class MutationObserverInit implements \ArrayAccess {
	// Dictionary type

	use \Wikimedia\IDLeDOM\Helper\MutationObserverInit;

	/**
	 * @return bool
	 */
	abstract public function getChildList(): bool;

	/**
	 * @return bool
	 */
	abstract public function getAttributes(): bool;

	/**
	 * @return bool
	 */
	abstract public function getCharacterData(): bool;

	/**
	 * @return bool
	 */
	abstract public function getSubtree(): bool;

	/**
	 * @return bool
	 */
	abstract public function getAttributeOldValue(): bool;

	/**
	 * @return bool
	 */
	abstract public function getCharacterDataOldValue(): bool;

	/**
	 * @return list<string>
	 */
	abstract public function getAttributeFilter(): array;

}

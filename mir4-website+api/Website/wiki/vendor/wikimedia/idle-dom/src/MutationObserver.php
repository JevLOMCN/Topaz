<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * MutationObserver
 *
 * @see https://dom.spec.whatwg.org/#interface-mutationobserver
 *
 * @phan-forbid-undeclared-magic-properties
 */
interface MutationObserver {

	/**
	 * @param Node $target
	 * @param MutationObserverInit|associative-array|null $options
	 * @return void
	 */
	public function observe( /* Node */ $target, /* ?mixed */ $options = null ): void;

	/**
	 * @return void
	 */
	public function disconnect(): void;

	/**
	 * @return list<MutationRecord>
	 */
	public function takeRecords(): array;

}

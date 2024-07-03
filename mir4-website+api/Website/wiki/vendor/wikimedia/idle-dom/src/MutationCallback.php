<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * MutationCallback
 *
 * @see https://dom.spec.whatwg.org/#callbackdef-mutationcallback
 *
 * @phan-forbid-undeclared-magic-properties
 */
interface MutationCallback {
	/**
	 * @param list<MutationRecord> $mutations
	 * @param MutationObserver $observer
	 * @return void
	 */
	public function invoke( array $mutations, /* MutationObserver */ $observer ): void;
}

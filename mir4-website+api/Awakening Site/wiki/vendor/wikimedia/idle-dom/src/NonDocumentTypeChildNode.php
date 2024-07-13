<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * NonDocumentTypeChildNode
 *
 * @see https://dom.spec.whatwg.org/#interface-nondocumenttypechildnode
 *
 * @property Element|null $previousElementSibling
 * @property Element|null $nextElementSibling
 * @phan-forbid-undeclared-magic-properties
 */
interface NonDocumentTypeChildNode {
	/**
	 * @return Element|null
	 */
	public function getPreviousElementSibling();

	/**
	 * @return Element|null
	 */
	public function getNextElementSibling();

}

<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * XMLSerializer
 *
 * @see https://dom.spec.whatwg.org/#interface-xmlserializer
 *
 * @phan-forbid-undeclared-magic-properties
 */
interface XMLSerializer {

	/**
	 * @param Node $root
	 * @return string
	 */
	public function serializeToString( /* Node */ $root ): string;

}

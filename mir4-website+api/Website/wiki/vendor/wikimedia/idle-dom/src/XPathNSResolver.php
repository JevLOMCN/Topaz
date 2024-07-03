<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * XPathNSResolver
 *
 * @see https://dom.spec.whatwg.org/#callbackdef-xpathnsresolver
 *
 * @phan-forbid-undeclared-magic-properties
 */
interface XPathNSResolver {
	/**
	 * @param ?string $prefix
	 * @return ?string
	 */
	public function lookupNamespaceURI( ?string $prefix ): ?string;

}

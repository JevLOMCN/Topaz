<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * CSSNamespaceRule
 *
 * @see https://dom.spec.whatwg.org/#interface-cssnamespacerule
 *
 * @property string $cssText
 * @property CSSRule|null $parentRule
 * @property CSSStyleSheet|null $parentStyleSheet
 * @property int $type
 * @property string $namespaceURI
 * @property string $prefix
 * @phan-forbid-undeclared-magic-properties
 */
interface CSSNamespaceRule extends CSSRule {
	// Direct parent: CSSRule

	/**
	 * @return string
	 */
	public function getNamespaceURI(): string;

	/**
	 * @return string
	 */
	public function getPrefix(): string;

}

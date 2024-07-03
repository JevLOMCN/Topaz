<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * CSSPageRule
 *
 * @see https://dom.spec.whatwg.org/#interface-csspagerule
 *
 * @property string $cssText
 * @property CSSRule|null $parentRule
 * @property CSSStyleSheet|null $parentStyleSheet
 * @property int $type
 * @property CSSRuleList $cssRules
 * @property string $selectorText
 * @property CSSStyleDeclaration $style
 * @phan-forbid-undeclared-magic-properties
 */
interface CSSPageRule extends CSSGroupingRule {
	// Direct parent: CSSGroupingRule

	/**
	 * @return string
	 */
	public function getSelectorText(): string;

	/**
	 * @param string $val
	 */
	public function setSelectorText( string $val ): void;

	/**
	 * @return CSSStyleDeclaration
	 */
	public function getStyle();

	/**
	 * @param string $val
	 */
	public function setStyle( string $val ): void;

}

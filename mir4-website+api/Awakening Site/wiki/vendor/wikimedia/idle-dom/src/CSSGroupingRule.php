<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * CSSGroupingRule
 *
 * @see https://dom.spec.whatwg.org/#interface-cssgroupingrule
 *
 * @property string $cssText
 * @property CSSRule|null $parentRule
 * @property CSSStyleSheet|null $parentStyleSheet
 * @property int $type
 * @property CSSRuleList $cssRules
 * @phan-forbid-undeclared-magic-properties
 */
interface CSSGroupingRule extends CSSRule {
	// Direct parent: CSSRule

	/**
	 * @return CSSRuleList
	 */
	public function getCssRules();

	/**
	 * @param string $rule
	 * @param int $index
	 * @return int
	 */
	public function insertRule( string $rule, int $index = 0 ): int;

	/**
	 * @param int $index
	 * @return void
	 */
	public function deleteRule( int $index ): void;

}

<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * CSSMarginRule
 *
 * @see https://dom.spec.whatwg.org/#interface-cssmarginrule
 *
 * @property string $cssText
 * @property CSSRule|null $parentRule
 * @property CSSStyleSheet|null $parentStyleSheet
 * @property int $type
 * @property string $name
 * @property CSSStyleDeclaration $style
 * @phan-forbid-undeclared-magic-properties
 */
interface CSSMarginRule extends CSSRule {
	// Direct parent: CSSRule

	/**
	 * @return string
	 */
	public function getName(): string;

	/**
	 * @return CSSStyleDeclaration
	 */
	public function getStyle();

	/**
	 * @param string $val
	 */
	public function setStyle( string $val ): void;

}

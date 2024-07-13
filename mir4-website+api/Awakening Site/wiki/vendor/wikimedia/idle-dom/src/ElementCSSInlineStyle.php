<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * ElementCSSInlineStyle
 *
 * @see https://dom.spec.whatwg.org/#interface-elementcssinlinestyle
 *
 * @property CSSStyleDeclaration $style
 * @phan-forbid-undeclared-magic-properties
 */
interface ElementCSSInlineStyle {
	/**
	 * @return CSSStyleDeclaration
	 */
	public function getStyle();

	/**
	 * @param string $val
	 */
	public function setStyle( string $val ): void;

}

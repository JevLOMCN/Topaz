<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * CSSRule
 *
 * @see https://dom.spec.whatwg.org/#interface-cssrule
 *
 * @property string $cssText
 * @property CSSRule|null $parentRule
 * @property CSSStyleSheet|null $parentStyleSheet
 * @property int $type
 * @phan-forbid-undeclared-magic-properties
 */
interface CSSRule {
	/**
	 * @return string
	 */
	public function getCssText(): string;

	/**
	 * @param string $val
	 */
	public function setCssText( string $val ): void;

	/**
	 * @return CSSRule|null
	 */
	public function getParentRule();

	/**
	 * @return CSSStyleSheet|null
	 */
	public function getParentStyleSheet();

	/**
	 * @return int
	 */
	public function getType(): int;

	/** @var int */
	public const STYLE_RULE = 1;

	/** @var int */
	public const CHARSET_RULE = 2;

	/** @var int */
	public const IMPORT_RULE = 3;

	/** @var int */
	public const MEDIA_RULE = 4;

	/** @var int */
	public const FONT_FACE_RULE = 5;

	/** @var int */
	public const PAGE_RULE = 6;

	/** @var int */
	public const MARGIN_RULE = 9;

	/** @var int */
	public const NAMESPACE_RULE = 10;

}

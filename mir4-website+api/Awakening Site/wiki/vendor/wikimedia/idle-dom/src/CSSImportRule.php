<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * CSSImportRule
 *
 * @see https://dom.spec.whatwg.org/#interface-cssimportrule
 *
 * @property string $cssText
 * @property CSSRule|null $parentRule
 * @property CSSStyleSheet|null $parentStyleSheet
 * @property int $type
 * @property string $href
 * @property MediaList $media
 * @property CSSStyleSheet $styleSheet
 * @phan-forbid-undeclared-magic-properties
 */
interface CSSImportRule extends CSSRule {
	// Direct parent: CSSRule

	/**
	 * @return string
	 */
	public function getHref(): string;

	/**
	 * @return MediaList
	 */
	public function getMedia();

	/**
	 * @param ?string $val
	 */
	public function setMedia( ?string $val ): void;

	/**
	 * @return CSSStyleSheet
	 */
	public function getStyleSheet();

}

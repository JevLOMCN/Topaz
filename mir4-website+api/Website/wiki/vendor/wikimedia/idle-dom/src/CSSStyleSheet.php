<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * CSSStyleSheet
 *
 * @see https://dom.spec.whatwg.org/#interface-cssstylesheet
 *
 * @property string $type
 * @property ?string $href
 * @property Element|ProcessingInstruction|null $ownerNode
 * @property CSSStyleSheet|null $parentStyleSheet
 * @property ?string $title
 * @property MediaList $media
 * @property bool $disabled
 * @property CSSRule|null $ownerRule
 * @property CSSRuleList $cssRules
 * @property CSSRuleList $rules
 * @phan-forbid-undeclared-magic-properties
 */
interface CSSStyleSheet extends StyleSheet {
	// Direct parent: StyleSheet

	/**
	 * @return CSSRule|null
	 */
	public function getOwnerRule();

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

	/**
	 * @param string $text
	 * @return void
	 */
	public function replaceSync( string $text ): void;

	/**
	 * @return CSSRuleList
	 */
	public function getRules();

	/**
	 * @param string $selector
	 * @param string $style
	 * @param ?int $index
	 * @return int
	 */
	public function addRule( string $selector = 'undefined', string $style = 'undefined', ?int $index = null ): int;

	/**
	 * @param int $index
	 * @return void
	 */
	public function removeRule( int $index = 0 ): void;

}

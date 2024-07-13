<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * CSSStyleDeclaration
 *
 * @see https://dom.spec.whatwg.org/#interface-cssstyledeclaration
 *
 * @property string $cssText
 * @property int $length
 * @property CSSRule|null $parentRule
 * @property string $cssFloat
 * @phan-forbid-undeclared-magic-properties
 */
interface CSSStyleDeclaration extends \ArrayAccess {
	/**
	 * @return string
	 */
	public function getCssText(): string;

	/**
	 * @param string $val
	 */
	public function setCssText( string $val ): void;

	/**
	 * @return int
	 */
	public function getLength(): int;

	/**
	 * @param int $index
	 * @return string
	 */
	public function item( int $index ): string;

	/**
	 * @param string $property
	 * @return string
	 */
	public function getPropertyValue( string $property ): string;

	/**
	 * @param string $property
	 * @return string
	 */
	public function getPropertyPriority( string $property ): string;

	/**
	 * @param string $property
	 * @param ?string $value
	 * @param string $priority
	 * @return void
	 */
	public function setProperty( string $property, ?string $value = '', string $priority = '' ): void;

	/**
	 * @param string $property
	 * @return string
	 */
	public function removeProperty( string $property ): string;

	/**
	 * @return CSSRule|null
	 */
	public function getParentRule();

	/**
	 * @return string
	 */
	public function getCssFloat(): string;

	/**
	 * @param ?string $val
	 */
	public function setCssFloat( ?string $val ): void;

}

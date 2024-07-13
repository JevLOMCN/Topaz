<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Stub;

use Exception;
use Wikimedia\IDLeDOM\CSSRule;

trait CSSStyleDeclaration {

	// Underscore is used to avoid conflicts with DOM-reserved names
	// phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
	// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

	/**
	 * @return Exception
	 */
	abstract protected function _unimplemented(): Exception;

	// phpcs:enable

	/**
	 * @return string
	 */
	public function getCssText(): string {
		throw self::_unimplemented();
	}

	/**
	 * @param string $val
	 */
	public function setCssText( string $val ): void {
		throw self::_unimplemented();
	}

	/**
	 * @return int
	 */
	public function getLength(): int {
		throw self::_unimplemented();
	}

	/**
	 * @param int $index
	 * @return string
	 */
	public function item( int $index ): string {
		throw self::_unimplemented();
	}

	/**
	 * @param string $property
	 * @return string
	 */
	public function getPropertyValue( string $property ): string {
		throw self::_unimplemented();
	}

	/**
	 * @param string $property
	 * @return string
	 */
	public function getPropertyPriority( string $property ): string {
		throw self::_unimplemented();
	}

	/**
	 * @param string $property
	 * @param ?string $value
	 * @param string $priority
	 * @return void
	 */
	public function setProperty( string $property, ?string $value = '', string $priority = '' ): void {
		throw self::_unimplemented();
	}

	/**
	 * @param string $property
	 * @return string
	 */
	public function removeProperty( string $property ): string {
		throw self::_unimplemented();
	}

	/**
	 * @return CSSRule|null
	 */
	public function getParentRule() {
		throw self::_unimplemented();
	}

	/**
	 * @return string
	 */
	public function getCssFloat(): string {
		throw self::_unimplemented();
	}

	/**
	 * @param ?string $val
	 */
	public function setCssFloat( ?string $val ): void {
		throw self::_unimplemented();
	}

}

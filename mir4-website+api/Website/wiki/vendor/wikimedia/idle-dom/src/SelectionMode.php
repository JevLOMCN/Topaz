<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * SelectionMode
 *
 * @see https://dom.spec.whatwg.org/#enumdef-selectionmode
 *
 * @phan-forbid-undeclared-magic-properties
 */
final class SelectionMode {
	/* Enumeration values */
	public const select = 'select';
	public const start = 'start';
	public const end = 'end';
	public const preserve = 'preserve';

	private function __construct() {
		/* Enumerations can't be instantiated */
	}

	// @phan-file-suppress PhanTypeInvalidThrowsIsInterface

	/**
	 * Throw a TypeError if the provided string is not a
	 * valid member of this enumeration.
	 *
	 * @param string $value The string to test
	 * @return string The provided string, if it is valid
	 * @throws \Wikimedia\IDLeDOM\TypeError if it is not valid
	 */
	public static function cast( string $value ): string {
		switch ( $value ) {
			case 'select':
			case 'start':
			case 'end':
			case 'preserve':
				return $value;
			default:
				throw new class() extends \Exception implements \Wikimedia\IDLeDOM\TypeError {
				};
		}
	}
}

<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * ShadowRootMode
 *
 * @see https://dom.spec.whatwg.org/#enumdef-shadowrootmode
 *
 * @phan-forbid-undeclared-magic-properties
 */
final class ShadowRootMode {
	/* Enumeration values */
	public const open = 'open';
	public const closed = 'closed';

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
			case 'open':
			case 'closed':
				return $value;
			default:
				throw new class() extends \Exception implements \Wikimedia\IDLeDOM\TypeError {
				};
		}
	}
}

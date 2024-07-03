<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * TextTrackKind
 *
 * @see https://dom.spec.whatwg.org/#enumdef-texttrackkind
 *
 * @phan-forbid-undeclared-magic-properties
 */
final class TextTrackKind {
	/* Enumeration values */
	public const subtitles = 'subtitles';
	public const captions = 'captions';
	public const descriptions = 'descriptions';
	public const chapters = 'chapters';
	public const metadata = 'metadata';

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
			case 'subtitles':
			case 'captions':
			case 'descriptions':
			case 'chapters':
			case 'metadata':
				return $value;
			default:
				throw new class() extends \Exception implements \Wikimedia\IDLeDOM\TypeError {
				};
		}
	}
}

<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * DOMParserSupportedType
 *
 * @see https://dom.spec.whatwg.org/#enumdef-domparsersupportedtype
 *
 * @phan-forbid-undeclared-magic-properties
 */
final class DOMParserSupportedType {
	/* Enumeration values */
	public const text_html = 'text/html';
	public const text_xml = 'text/xml';
	public const application_xml = 'application/xml';
	public const application_xhtml_xml = 'application/xhtml+xml';
	public const image_svg_xml = 'image/svg+xml';

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
			case 'text/html':
			case 'text/xml':
			case 'application/xml':
			case 'application/xhtml+xml':
			case 'image/svg+xml':
				return $value;
			default:
				throw new class() extends \Exception implements \Wikimedia\IDLeDOM\TypeError {
				};
		}
	}
}

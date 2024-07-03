<?php

namespace Test\Parsoid\Core;

use Wikimedia\Parsoid\Core\Sanitizer;

/**
 * @coversDefaultClass \Wikimedia\Parsoid\Core\Sanitizer
 */
class SanitizerUnitTest extends \PHPUnit\Framework\TestCase {
	private const UTF8_REPLACEMENT = "\xef\xbf\xbd";

	/**
	 * @dataProvider provideDecodeCharReferences
	 * @covers ::decodeCharReferences
	 */
	public function testDecodeCharReferences( string $expected, string $input ) {
		$this->assertSame( $expected, Sanitizer::decodeCharReferences( $input ) );
	}

	public function provideDecodeCharReferences() {
		return [
			'decode named entities' => [
				"\u{00E9}cole",
				'&eacute;cole',
			],
			'decode numeric entities' => [
				"\u{0108}io bonas dans l'\u{00E9}cole!",
				"&#x108;io bonas dans l'&#233;cole!",
			],
			'decode mixed numeric/named entities' => [
				"\u{0108}io bonas dans l'\u{00E9}cole!",
				"&#x108;io bonas dans l'&eacute;cole!",
			],
			'decode mixed complex entities' => [
				"\u{0108}io bonas dans l'\u{00E9}cole! (mais pas &#x108;io dans l'&eacute;cole)",
				"&#x108;io bonas dans l'&eacute;cole! (mais pas &amp;#x108;io dans l'&#38;eacute;cole)",
			],
			'Invalid ampersand' => [
				'a & b',
				'a & b',
			],
			'Invalid named entity' => [
				'&foo;',
				'&foo;',
			],
			'Invalid numbered entity (decimal)' => [
				self::UTF8_REPLACEMENT,
				"&#888888888888888888;",
			],
			'Invalid numbered entity (hex)' => [
				self::UTF8_REPLACEMENT,
				"&#x88888888888888888;",
			],
			// These cases are also "very large" numbers, but they will
			// truncate down to ASCII.  So be careful.
			'Invalid numbered entity w/ valid truncation (decimal)' => [
				self::UTF8_REPLACEMENT,
				"&#18446744073709551681;",
			],
			'Invalid numbered entity w/ valid truncation (hex)' => [
				self::UTF8_REPLACEMENT,
				"&#x10000000000000041;",
			],
		];
	}

	// testDecodeTagAttributes from upstream omitted.

	/**
	 * @dataProvider provideCssCommentsFixtures
	 * @covers ::checkCss
	 */
	public function testCssCommentsChecking( $expected, $css, $message = '' ) {
		$this->assertSame( $expected,
			Sanitizer::checkCss( $css ),
			$message
		);
	}

	public static function provideCssCommentsFixtures() {
		/** [ <expected>, <css>, [message] ] */
		return [
			// Valid comments spanning entire input
			[ '/**/', '/**/' ],
			[ '/* comment */', '/* comment */' ],
			// Weird stuff
			[ ' ', '/****/' ],
			[ ' ', '/* /* */' ],
			[ 'display: block;', "display:/* foo */block;" ],
			[ 'display: block;', "display:\\2f\\2a foo \\2a\\2f block;",
				'Backslash-escaped comments must be stripped (T30450)' ],
			[ '', '/* unfinished comment structure',
				'Remove anything after a comment-start token' ],
			[ '', "\\2f\\2a unifinished comment'",
				'Remove anything after a backslash-escaped comment-start token' ],
			[ '/* insecure input */', 'width: expression(1+1);' ],
			[ '/* insecure input */', 'background-image: image(asdf.png);' ],
			[ '/* insecure input */', 'background-image: -webkit-image(asdf.png);' ],
			[ '/* insecure input */', 'background-image: -moz-image(asdf.png);' ],
			[ '/* insecure input */', 'background-image: image-set("asdf.png" 1x, "asdf.png" 2x);' ],
			[
				'/* insecure input */',
				'background-image: -webkit-image-set("asdf.png" 1x, "asdf.png" 2x);'
			],
			[
				'/* insecure input */',
				'background-image: -moz-image-set("asdf.png" 1x, "asdf.png" 2x);'
			],
			[ '/* insecure input */', 'foo: attr( title, url );' ],
			[ '/* insecure input */', 'foo: attr( title url );' ],
		];
	}

	// testEscapeHtmlAllowEntities from upstream omitted.

	/**
	 * @dataProvider provideIsReservedDataAttribute
	 * @covers ::isReservedDataAttribute
	 */
	public function testIsReservedDataAttribute( $attr, $expected ) {
		$this->assertSame( $expected, Sanitizer::isReservedDataAttribute( $attr ) );
	}

	public static function provideIsReservedDataAttribute() {
		return [
			[ 'foo', false ],
			[ 'data', false ],
			[ 'data-foo', false ],
			// [ 'data-mw', true ], // PARSOID-SPECIFIC BEHAVIOR CHANGE
			[ 'data-ooui', true ],
			// [ 'data-parsoid', true ], // PARSOID-SPECIFIC BEHAVIOR CHANGE
			// [ 'data-mw-foo', true ], // PARSOID-SPECIFIC BEHAVIOR CHANGE
			[ 'data-ooui-foo', true ],
			// [ 'data-mwfoo', true ], // PARSOID-SPECIFIC BEHAVIOR CHANGE
		];
	}

	// testStripAllTags from upstream omitted.
}

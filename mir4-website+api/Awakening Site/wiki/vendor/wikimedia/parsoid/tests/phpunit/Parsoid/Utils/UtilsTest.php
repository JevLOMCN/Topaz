<?php

namespace Test\Parsoid\Utils;

use Wikimedia\Parsoid\Utils\Utils;

/**
 * @coversDefaultClass \Wikimedia\Parsoid\Utils\Utils
 */
class UtilsTest extends \PHPUnit\Framework\TestCase {
	/**
	 * @covers ::decodeWtEntities
	 * @dataProvider provideDecodeWtEntities
	 */
	public function testDecodeWtEntities( $wt, $expected ) {
		$actual = Utils::decodeWtEntities( $wt );
		$this->assertEquals( $expected, $actual );
	}

	public function provideDecodeWtEntities() {
		// Easy cases...
		yield "Lowercase &" => [ '&amp;', '&' ];
		yield "Uppercase &" => [ '&AMP;', '&' ];
		// Ensure that HTML5 "semicolon-less" entities are not
		// decoded -- these are decoded in HTML5 but not in wikitext!
		yield "Semicolon-less &" => [ '&ampamp;', '&ampamp;' ];
		yield "Semicolon-less query" => [ '&prod=foo', '&prod=foo' ];
		// Exhaustive test against Sanitizer::validateCodePoint()
		$checkEntity = function ( int $cp, string $s ) {
			$expected = self::validateCodePoint( $cp ) ?
					  mb_chr( $cp, "utf-8" ) : $s;
			yield $s => [ $s, $expected ];
		};
		$check = static function ( int $cp ) use ( $checkEntity ) {
			yield from $checkEntity( $cp, "&#$cp;" );
			yield from $checkEntity( $cp, strtolower( "&#x" . dechex( $cp ) . ";" ) );
			yield from $checkEntity( $cp, strtoupper( "&#x" . dechex( $cp ) . ";" ) );
		};
		for ( $cp = 0; $cp < 0x100; $cp++ ) {
			yield from $check( $cp );
		}
		for ( $cp = 0xD700; $cp < 0xE010; $cp++ ) {
			yield from $check( $cp );
		}
		for ( $cp = 0xFFF0; $cp < 0x10010; $cp++ ) {
			yield from $check( $cp );
		}
		for ( $cp = 0x10FFF0; $cp < 0x110010; $cp++ ) {
			yield from $check( $cp );
		}
	}

	/**
	 * This is a direct copy of Sanitizer::validateCodepoint; when/if
	 * Parsoid is integrated into MediaWiki we could replace this with
	 * a direct call.
	 *
	 * @param int $codepoint Unicode codepoint.
	 *
	 * @return bool
	 */
	private static function validateCodePoint( int $codepoint ): bool {
		# U+000C is valid in HTML5 but not allowed in XML.
		# U+000D is valid in XML but not allowed in HTML5.
		# U+007F - U+009F are disallowed in HTML5 (control characters).
		return $codepoint == 0x09
			|| $codepoint == 0x0a
			|| ( $codepoint >= 0x20 && $codepoint <= 0x7e )
			|| ( $codepoint >= 0xa0 && $codepoint <= 0xd7ff )
			|| ( $codepoint >= 0xe000 && $codepoint <= 0xfffd )
			|| ( $codepoint >= 0x10000 && $codepoint <= 0x10ffff );
	}

	/**
	 * @covers ::escapeWtEntities
	 * @dataProvider provideEscapeWtEntities
	 */
	public function testEscapeWtEntities( $text, $expected ) {
		$actual = Utils::escapeWtEntities( $text );
		$this->assertEquals( $expected, $actual );
	}

	public function provideEscapeWtEntities() {
		return [
			[ '&amp;', '&amp;amp;' ],
			// Not all entities, just those that are valid
			[ 'hello&goodbye', 'hello&goodbye' ],
			[ 'hello&goodbye;', 'hello&goodbye;' ],
		];
	}

	/**
	 * @covers ::escapeHtml
	 * @dataProvider provideEscapeHtml
	 */
	public function testEscapeHtml( $text, $expected ) {
		$actual = Utils::escapeHtml( $text );
		$this->assertEquals( $expected, $actual );
	}

	public function provideEscapeHtml() {
		return [
			[ 'only 5 characters escaped', 'only 5 characters escaped' ],
			[ '<>&"\'', '&lt;&gt;&amp;&quot;&apos;' ],
		];
	}

	/**
	 * @covers ::entityEncodeAll
	 * @dataProvider provideEntityEncodeAll
	 */
	public function testEntityEncodeAll( $text, $expected ) {
		$actual = Utils::entityEncodeAll( $text );
		$this->assertEquals( $expected, $actual );
	}

	public function provideEntityEncodeAll() {
		return [
			[ 'Even ASCII', '&#x45;&#x76;&#x65;&#x6E;&#x20;&#x41;&#x53;&#x43;&#x49;&#x49;' ],
			[ 'is encoded', '&#x69;&#x73;&#x20;&#x65;&#x6E;&#x63;&#x6F;&#x64;&#x65;&#x64;' ],
			[ "&>", '&#x26;&#x3E;' ],
			// Some entities use special forms
			[ "\u{00A0}", '&nbsp;' ],
			[ "\u{000D}", '&#x0D;' ],
			// Even Unicode characters in the "Astral plane" work correctly
			[ "\u{1F4A9}", '&#x1F4A9;' ],
		];
	}

	/**
	 * @dataProvider provideDecodeURI
	 * @covers ::decodeURI
	 * @covers ::decodeURIComponent
	 */
	public function testDecodeURI( $input, $expect1, $expect2 = null ) {
		$this->assertSame( $expect1, Utils::decodeURIComponent( $input ) );
		$this->assertSame( $expect2 ?? $expect1, Utils::decodeURI( $input ) );
	}

	public function provideDecodeURI() {
		return [
			'Simple example' => [ 'abc %66%6f%6f%c2%a0%62%61%72', "abc foo\xc2\xa0bar" ],
			'Non-BMP example' => [ '%66%6f%6f%f0%9f%92%a9%62%61%72', 'foo💩bar' ],
			'Reserved chars (lowercase hex)' => [
				'%22%23%24%25%26%27%28%29%2a%2b%2c%2d%2e%2f%30%31%32%33%34%35%36%37%38%39%3a%3b%3c%3d%3e%3f%40',
				'"#$%&\'()*+,-./0123456789:;<=>?@',
				'"%23%24%%26\'()*%2b%2c-.%2f0123456789%3a%3b<%3d>%3f%40',
			],
			'Reserved chars (uppercase hex)' => [
				'%22%23%24%25%26%27%28%29%2A%2B%2C%2D%2E%2F%30%31%32%33%34%35%36%37%38%39%3A%3B%3C%3D%3E%3F%40',
				'"#$%&\'()*+,-./0123456789:;<=>?@',
				'"%23%24%%26\'()*%2B%2C-.%2F0123456789%3A%3B<%3D>%3F%40',
			],
			'Reserved chars (literals aren\'t encoded)' => [
				'"#$&\'()*+,-./0123456789:;<=>?@',
				'"#$&\'()*+,-./0123456789:;<=>?@',
			],
			'Invalid byte' => [ '%66%6f%6f%aA%62%61%72', 'foo%aAbar' ],
			'Overlong sequence' => [ '%66%6f%6f%c1%98%62%61%72', 'foo%c1%98bar' ],
			'Out of range sequence' => [ '%66%6f%6f%f4%90%80%80%62%61%72', 'foo%f4%90%80%80bar' ],
			'Truncated sequence' => [ '%66%6f%6f%f0%9f%92%c9%62%61%72', 'foo%f0%9f%92%c9bar' ],
			'Too many continuation bytes' => [ '%66%6f%6f%c2%a0%a0%62%61%72', "foo\xc2\xa0%a0bar" ],
			'Invalid percent-sequence' => [ '%66%6f%6f%aG%%62%61%72', 'foo%aG%bar' ],
			'Truncated percent-sequence' => [ '%66%6f%6', 'fo%6' ],
			'Truncated percent-sequence (2)' => [ '%66%6f%', 'fo%' ],
		];
	}

}

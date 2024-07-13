<?php

namespace Test\Parsoid\Utils;

use PHPUnit\Framework\TestCase;
use Wikimedia\Parsoid\Utils\PHPUtils;

/**
 * @coversDefaultClass \Wikimedia\Parsoid\Utils\PHPUtils
 */
class PHPUtilsTest extends TestCase {
	/**
	 * @covers \Wikimedia\Parsoid\Utils\PHPUtils::reStrip
	 * @dataProvider provideReStrip
	 */
	public function testReStrip( $re, $delimiter, $expected ) {
		$actual = PHPUtils::reStrip( $re, $delimiter );
		$this->assertEquals( $expected, $actual );
	}

	public function provideReStrip() {
		return [
			[ '/abc/', null, 'abc' ], // easy case
			[ ' /abc/mA', null, 'abc' ], // flags and leading whitespace
			[ '~ab/de~x', '/', 'ab\\/de' ], // new delimiter
			[ '~ab\/de~x', '/', 'ab\/de' ], // already escaped
			[ "~\\\u{1F4A9}~u", '/', "\\\u{1F4A9}" ], // unicode safe
		];
	}

	/**
	 * @covers \Wikimedia\Parsoid\Utils\PHPUtils::safeSubstr
	 * @covers \Wikimedia\Parsoid\Utils\PHPUtils::assertValidUTF8
	 * @dataProvider provideSafeSubstr
	 */
	public function testSafeSubstr( string $s, int $start, int $end, bool $expectedOk ) {
		// The full string needs to be valid UTF-8
		PHPUtils::assertValidUTF8( $s );
		PHPUtils::safeSubstr( $s, 0, null, true );

		// Fast verification
		$s1 = false;
		try {
			$s1 = PHPUtils::safeSubstr( $s, $start, $end - $start );
			$ok = true;
		} catch ( \Exception $e ) {
			$ok = false;
		}
		$this->assertEquals( $expectedOk, $ok );

		// Now try full string verification
		$s2 = substr( $s, $start, $end - $start );
		try {
			PHPUtils::assertValidUTF8( $s2 );
			$ok = true;
		} catch ( \Exception $e ) {
			$ok = false;
		}
		$this->assertEquals( $expectedOk, $ok );
		if ( $ok ) {
			$this->assertEquals( $s1, $s2 );
		}

		// Full string verification, take 2
		$s3 = false;
		try {
			$s3 = PHPUtils::safeSubstr( $s, $start, $end - $start, true );
			$ok = true;
		} catch ( \Exception $e ) {
			$ok = false;
		}
		$this->assertEquals( $expectedOk, $ok );
		$this->assertEquals( $s1, $s3 );
	}

	public function provideSafeSubstr() {
		$cases = [
			// Simple case
			[ 'input' => 'abc', 'indices' => [
				[ 0, 3, true ], [ 1, 3, true ], [ 2, 3, true ],
				[ 0, 2, true ], [ 0, 1, true ], [ 0, 0, true ],
			] ],
			// 1-byte codepoint, then 2-byte codepoint, then 3-byte codepoint,
			// then 4-byte codepoint = 1+2+3+4 = 10 bytes long
			[ 'input' => "A\u{0531}\u{4EBA}\u{1F4A9}", 'indices' => [
				// truncating the head
				[ 0, 10, true ], [ 1, 10, true ], [ 2, 10, false ],
				[ 3, 10, true ], [ 4, 10, false ], [ 5, 10, false ],
				[ 6, 10, true ], [ 7, 10, false ], [ 8, 10, false ],
				[ 9, 10, false ], [ 10, 10, true ],
				// truncating the tail
				[ 0, 0, true ], [ 0, 1, true ], [ 0, 2, false ],
				[ 0, 3, true ], [ 0, 4, false ], [ 0, 5, false ],
				[ 0, 6, true ], [ 0, 7, false ], [ 0, 8, false ],
				[ 0, 9, false ], [ 0, 10, true ],
				// three examples from the middle
				[ 1, 6, true ],
				[ 2, 6, false ],
				[ 1, 7, false ],
			] ],
		];
		// expand these by duplicating the input string for better
		// diagnostics if things fail
		foreach ( $cases as $c ) {
			foreach ( $c['indices'] as $idx ) {
				yield [ $c['input'], $idx[0], $idx[1], $idx[2] ];
			}
		}
	}

	/**
	 * @covers ::assertValidUTF8
	 * @dataProvider provideAssertValidUTF8
	 */
	public function testAssertValidUTF8( string $s, bool $expectedOk ) {
		if ( !$expectedOk ) {
			$this->expectException( \Exception::class );
		}
		PHPUtils::assertValidUTF8( $s );
		// This assertion doesn't do anything, but it increases the
		// assertion counter so PHPUnit knows something was checked.
		$this->assertTrue( $expectedOk );
	}

	public function provideAssertValidUTF8() {
		// Our UTF8 validity checker uses `//u` as an optimization, which
		// relies on PHP's pcre extension to pass PCRE2_UTF and not
		// PCRE2_NO_UTF_CHECK or PCRE2_MATCH_INVALID_UTF8 through to pcre,
		// and then requires pcre to actually implement a thorough check;
		// https://www.pcre.org/current/doc/html/pcre2unicode.html mentions
		// "the check is applied only to that part of the subject that could
		// be inspected during matching" *when called with a non-zero offset*;
		// if this optimization were in the future extended to calls with
		// a zero offset (the way we use pcre_match) or PHP were to optimize
		// string representation such that the low-level string passed into
		// pcre were to have a non-zero offset, then conceivable PCRE2 could
		// skip the check entirely on a zero-length match like `//u`.
		// Anyway, these test cases *attempt* to protect against future
		// regressions of this sort.
		return [
			// valid input should be valid
			[ 'abcdef', true ],
			// bad input at start and end should be caught
			[ "\x80", false ],
			[ "abcdef\x80", false ],
			// two byte sequences
			[ "xx\xC2\x80xx", true ], // U+0080, valid
			[ "xx\xC0\x80xx", false ], // invalid (would encode U+0000)
			[ "xx\xC1\xBFxx", false ], // invalid (would encode U+007F)
			[ "xx\xC2\xC2xx", false ],
			[ "xx\xC2\x80\x80xx", false ],
			[ "xx\xC2", false ], // at end of string
			 // three byte sequences
			[ "xx\xE0\xA0\x80xx", true ], // U+0800, valid
			[ "xx\xE0\x80\x80xx", false ], // invalid (would encode U+0000)
			[ "xx\xE0\x9F\xBFxx", false ], // invalid (would encode U+07FF)
			[ "xx\xE0\xA0\xA0\xA0xx", false ],
			[ "xx\xE0x\xA0xxx", false ],
			[ "xx\xE0\xA0xxxx", false ],
			[ "xx\xE0\xA0", false ], // at end of string
			[ "xx\xE0", false ],
			 // four byte sequences
			[ "xx\xF0\x90\x80\x80xxx", true ], // U+10000, valid
			[ "xx\xF0\x80\x80\x80xxx", false ], // invalid (would encode U+0000)
			[ "xx\xF0\x8F\xBF\xBFxxx", false ], // invalid (would encode U+FFFF)
			[ "xx\xF0x\x90\x90xxx", false ],
			[ "xx\xF0\x90x\x90xxx", false ],
			[ "xx\xF0\x90\x90xxxx", false ],
			[ "xx\xF0\x90\x90", false ], // at end of string
			[ "xx\xF0\x90", false ],
			[ "xx\xF0", false ],
			// five byte sequences (obsolete RFC2279, should be caught)
			[ "xx\xF8\x88\x80\x80\x80xx", false ], // U+20 0000
			[ "xx\xFB\xBF\xBF\xBF\xBFxx", false ], // U+3FF FFFF
			[ "xx\xF8\x80\x80\x80\x80xx", false ], // (would encode U+0000)
			// six byte sequences (obsolete RFC2279, should be caught)
			[ "xx\xFC\x84\x80\x80\x80\x80xx", false ], // U+400 0000
			[ "xx\xFC\x80\x80\x80\x80\x80xx", false ], // (would encode U+0000)
			// characters above 10FFFF should be caught
			[ "xx\xF4\x8F\xBF\xBFxx", true ], // U+10FFFF
			[ "xx\xF4\x90\x80\x80xx", false ], // U+110000
			[ "xx\xF4\x9F\xBF\xBFxx", false ], // U+11FFFF
			[ "xx\xF7\xBF\xBF\xBFxx", false ], // U+1FFFFF
			// surrogate characters in the range D800-DFFF should be caught
			[ "xx\xED\x9F\xBFxx", true ], // U+D7FF
			[ "xx\xED\xA0\x80xx", false ], // U+D800
			[ "xx\xED\xAF\xBFxx", false ], // U+DBFF
			[ "xx\xED\xB0\x80xx", false ], // U+DC00
			[ "xx\xED\xBF\xBFxx", false ], // U+DFFF
			// and make sure it's not just unpaired surrogates which are
			// being caught, try some valid surrogate pairs as well
			[ "xx\xED\xA0\x81\xED\xB0\xB7xx", false ], // U+10437 -> U+D801 U+DC37
			[ "xx\xED\xA1\x92\xED\xBD\xA2xx", false ], // U+24B62 -> U+D852 U+DF62
		];
	}
}

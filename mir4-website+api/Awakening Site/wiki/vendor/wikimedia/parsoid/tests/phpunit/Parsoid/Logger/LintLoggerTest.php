<?php

namespace Test\Parsoid\Logger;

use Wikimedia\Parsoid\Logger\LintLogger;
use Wikimedia\Parsoid\Mocks\MockEnv;

/**
 * @coversDefaultClass \Wikimedia\Parsoid\Logger\LintLogger
 */
class LintLoggerTest extends \PHPUnit\Framework\TestCase {
	/**
	 * @covers ::convertDSROffsets()
	 * @dataProvider provideConvertDSROffsets
	 */
	public function testConvertDSROffsets(
		string $str, array $byteStrs, array $byteDSR, array $ucs2DSR
	) {
		// Verify that the test is well-formed
		$testStrs = [
			substr( $str, $byteDSR[0], $byteDSR[1] - $byteDSR[0] ),
			$byteDSR[2] > 0 ? substr( $str, $byteDSR[0], $byteDSR[2] ) : "",
			$byteDSR[3] > 0 ? substr( $str, $byteDSR[1] - $byteDSR[3], $byteDSR[3] ) : ""
		];
		$this->assertSame( $testStrs, $byteStrs, "Double check" );

		// Now, verify that offset conversion is correct
		$env = new MockEnv( [ "pageContent" => $str ] );
		$lints = [ [ "dsr" => $byteDSR ] ];
		LintLogger::convertDSROffsets( $env, $lints );
		$this->assertSame( $lints[0]["dsr"], $ucs2DSR, "byte → ucs2" );
	}

	public static function provideConvertDSROffsets(): iterable {
		# This string and the offsets are borrowed from Utils\TokenUtilsTest.php
		# Ensure that we have char from each UTF-8 class here.
		#
		#      "foo bár 💩💩 baz AԱ人💩"
		# char  012345678 9 01234567 8
		# ucs   012345678 0 23456789 0
		# byte  012345789 3 78901235 8
		#
		$str = "foo bár \u{1F4A9}\u{1F4A9} baz A\u{0531}\u{4EBA}\u{1F4A9}";

		# These are arbitrary DSRs. All we care in this test is that
		# dsr offsts are properly converted. They needn't correspond
		# to actual lint errors.
		$lints = [
			[ // Entire string
				"byteStrs" => [ $str, "", "" ],
				"byte" => [ 0, 32, 0, 0 ],
				"ucs2" => [ 0, 22, 0, 0 ]
			],
			[ // foo
				"byteStrs" => [ "foo", "f", "o" ],
				"byte" => [ 0, 3, 1/*f*/, 1/*o*/ ],
				"ucs2" => [ 0, 3, 1/*f*/, 1/*f*/ ]
			],
			[ // bár
				"byteStrs" => [ "bár", "", "ár" ],
				"byte" => [ 4, 8, 0, 3/*ár*/ ],
				"ucs2" => [ 4, 7, 0, 2/*ár*/ ]
			],
			[ // \u{1F4A9} baz A\u{0531}
				"byteStrs" => [ "\u{1F4A9} baz A\u{0531}", "\u{1F4A9}", "A\u{0531}" ],
				"byte" => [ 13, 25, 4/*\u{1F4A9}*/, 3/*A\u{0531}*/ ],
				"ucs2" => [ 10, 19, 2/*\u{1F4A9}*/, 2/*A\u{0531}*/ ]
			]
		];

		foreach ( $lints as $l ) {
			yield [
				$str,
				$l['byteStrs'],
				$l['byte'],
				$l['ucs2'],
			];
		}
	}
}

<?php

namespace Test\Parsoid\Language;

use PHPUnit\Framework\TestCase;
use Wikimedia\Bcp47Code\Bcp47CodeValue;
use Wikimedia\LangConv\ReplacementMachine;
use Wikimedia\Parsoid\Language\LanguageConverter;
use Wikimedia\Parsoid\Mocks\MockEnv;
use Wikimedia\Parsoid\Utils\DOMCompat;

class KuTest extends TestCase {

	private const CODES = [ "ku-arab", "ku-latn" ];

	// phpcs:disable Generic.Files.LineLength.TooLong
	private const TEST_CASES = [
		[
			'title' => 'Convert one char',
			'output' => [
				'ku' => "١",
				'ku-arab' => "١",
				'ku-latn' => '1'
			],
			'input' => "١"
		],
		[
			'title' => 'Convert ku-latn',
			'output' => [
				'ku' => "Wîkîpediya ensîklopediyeke azad bi rengê wîkî ye.",
				// XXX broken!
				// 'ku-arab' => 'ویکیپەدیائە نسیکلۆپەدیەکەئا زاد ب رەنگێ ویکی یە.',
				'ku-latn' => "Wîkîpediya ensîklopediyeke azad bi rengê wîkî ye."
			],
			'input' => "Wîkîpediya ensîklopediyeke azad bi rengê wîkî ye."
		],
		[
			'title' => 'Convert ku-arab',
			'output' => [
				'ku' => "ویکیپەدیا ەنسیکلۆپەدیەکەئا زاد ب رەنگێ ویکی یە.",
				'ku-arab' => "ویکیپەدیا ەنسیکلۆپەدیەکەئا زاد ب رەنگێ ویکی یە.",
				'ku-latn' => "wîkîpedîa ensîklopedîekea zad b rengê wîkî îe."
			],
			'input' => "ویکیپەدیا ەنسیکلۆپەدیەکەئا زاد ب رەنگێ ویکی یە."
		]
	];

	/** @var ReplacementMachine */
	private static $machine;

	public static function setUpBeforeClass(): void {
		$lang = LanguageConverter::loadLanguage(
			new MockEnv( [] ),
			new Bcp47CodeValue( 'ku' )
		);
		self::$machine = $lang->getConverter()->getMachine();
	}

	public static function tearDownAfterClass(): void {
		self::$machine = null;
	}

	/**
	 * @covers \Wikimedia\LangConv\FST
	 * @dataProvider provideKu
	 */
	public function testKu( string $title, array $output, string $input, ?string $invertCode ) {
		foreach ( self::CODES as $variantCode ) {
			if ( !array_key_exists( $variantCode, $output ) ) {
				continue;
			}

			$doc = DOMCompat::newDocument( true );
			$out = self::$machine->convert(
				$doc, $input, $variantCode,
				$invertCode ?? $this->getInvertCode( $variantCode )
			);
			$expected = $output[$variantCode];
			$this->assertEquals( $expected, $out->textContent );
		}
	}

	public function provideKu() {
		return array_map( static function ( $item ) {
			return [
				$item['title'],
				$item['output'],
				$item['input'],
				$item['code'] ?? null
			];
		}, self::TEST_CASES );
	}

	private function getInvertCode( $variantCode ) {
		return $variantCode === "ku-arab" ? "ku-latn" : "ku-arab";
	}

}

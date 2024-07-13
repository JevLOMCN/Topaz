<?php

namespace Test\Parsoid\Language;

use PHPUnit\Framework\TestCase;
use Wikimedia\Bcp47Code\Bcp47CodeValue;
use Wikimedia\LangConv\ReplacementMachine;
use Wikimedia\Parsoid\Language\LanguageConverter;
use Wikimedia\Parsoid\Mocks\MockEnv;
use Wikimedia\Parsoid\Utils\DOMCompat;

class ZhTest extends TestCase {

	private const CODES = [
		"zh-cn", "zh-sg", "zh-my", "zh-hans", "zh-tw", "zh-hk", "zh-mo", "zh-hant"
	];

	// phpcs:disable Generic.Files.LineLength.TooLong
	private const TEST_CASES = [
			[
				'title' => 'Plain hant -> hans',
				'output' => [
					'zh' => "㑯",
					'zh-hans' => "㑔",
					'zh-hant' => "㑯",
					'zh-cn' => "㑔",
					'zh-hk' => "㑯",
					'zh-mo' => "㑯",
					'zh-my' => "㑔",
					'zh-sg' => "㑔",
					'zh-tw' => "㑯"
				],
				'input' => "㑯"
			],
			[
				'title' => 'Plain hans -> hant',
				'output' => [
					'zh' => "㐷",
					'zh-hans' => "㐷",
					'zh-hant' => "傌",
					'zh-cn' => "㐷",
					'zh-hk' => "傌",
					'zh-mo' => "傌",
					'zh-my' => "㐷",
					'zh-sg' => "㐷",
					'zh-tw' => "傌"
				],
				'input' => "㐷"
			],
			[
				'title' => 'zh-cn specific',
				'output' => [
					'zh' => "仲介",
					'zh-hans' => "仲介",
					'zh-hant' => "仲介",
					'zh-cn' => "中介",
					'zh-hk' => "仲介",
					'zh-mo' => "仲介",
					'zh-my' => "中介",
					'zh-sg' => "中介",
					'zh-tw' => "仲介"
				],
				'input' => "仲介"
			],
			[
				'title' => 'zh-hk specific',
				'output' => [
					'zh' => "中文里",
					'zh-hans' => "中文里",
					'zh-hant' => "中文裡",
					'zh-cn' => "中文里",
					'zh-hk' => "中文裏",
					'zh-mo' => "中文裏",
					'zh-my' => "中文里",
					'zh-sg' => "中文里",
					'zh-tw' => "中文裡"
				],
				'input' => "中文里"
			],
			[
				'title' => 'zh-tw specific',
				'output' => [
					'zh' => "甲肝",
					'zh-hans' => "甲肝",
					'zh-hant' => "甲肝",
					'zh-cn' => "甲肝",
					'zh-hk' => "甲肝",
					'zh-mo' => "甲肝",
					'zh-my' => "甲肝",
					'zh-sg' => "甲肝",
					'zh-tw' => "A肝"
				],
				'input' => "甲肝"
			],
			[
				'title' => 'zh-tw overrides zh-hant',
				'output' => [
					'zh' => "账",
					'zh-hans' => "账",
					'zh-hant' => "賬",
					'zh-cn' => "账",
					'zh-hk' => "賬",
					'zh-mo' => "賬",
					'zh-my' => "账",
					'zh-sg' => "账",
					'zh-tw' => "帳"
				],
				'input' => "账"
			],
			[
				'title' => 'zh-hk overrides zh-hant',
				'output' => [
					'zh' => "一地里",
					'zh-hans' => "一地里",
					'zh-hant' => "一地裡",
					'zh-cn' => "一地里",
					'zh-hk' => "一地裏",
					'zh-mo' => "一地裏",
					'zh-my' => "一地里",
					'zh-sg' => "一地里",
					'zh-tw' => "一地裡"
				],
				'input' => "一地里"
			]
		];

	/** @var ReplacementMachine */
	private static $machine;

	public static function setUpBeforeClass(): void {
		$lang = LanguageConverter::loadLanguage(
			new MockEnv( [] ),
			new Bcp47CodeValue( 'zh' )
		);
		self::$machine = $lang->getConverter()->getMachine();
	}

	public static function tearDownAfterClass(): void {
		self::$machine = null;
	}

	/**
	 * @covers \Wikimedia\LangConv\FST
	 * @dataProvider provideZh
	 */
	public function testZh( string $title, array $output, string $input, ?string $invertCode ) {
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

	public function provideZh() {
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
		return (bool)preg_match( '/^zh-(cn|sg|my|hans)$/', $variantCode ) ? "zh-hant" : "zh-hans";
	}

}

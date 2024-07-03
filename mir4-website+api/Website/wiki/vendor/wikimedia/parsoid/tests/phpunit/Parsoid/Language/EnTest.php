<?php

namespace Test\Parsoid\Language;

use PHPUnit\Framework\TestCase;
use Wikimedia\Bcp47Code\Bcp47CodeValue;
use Wikimedia\LangConv\ReplacementMachine;
use Wikimedia\Parsoid\Language\LanguageConverter;
use Wikimedia\Parsoid\Mocks\MockEnv;
use Wikimedia\Parsoid\Utils\DOMCompat;

class EnTest extends TestCase {

	private const CODES = [ 'en', 'en-x-piglatin' ];

	// phpcs:disable Generic.Files.LineLength.TooLong
	private const TEST_CASES = [
		[
			'title' => 'Converting to Pig Latin',
			'output' => [
				'en' => "123 Pigpen pig latin of 123 don't stop believing in yourself queen JavaScript NASA",
				'en-x-piglatin' => "123 Igpenpay igpay atinlay ofway 123 on'tday opstay elievingbay inway ourselfyay eenquay JavaScript NASA"
			],
			'input' => "123 Pigpen pig latin of 123 don't stop believing in yourself queen JavaScript NASA",
			'code' => 'en'
		],
		[
			'title' => 'Converting from Pig Latin',
			'output' => [
				'en' => "123 Pigpen pig latin of 123 don't tops believing in yourself queen avaScriptJay ASANAY",
				'en-x-piglatin' => "123 Igpenpayway igpayway atinlayway ofwayway 123 on'tdayway opstayway elievingbayway inwayway ourselfyayway eenquayway avaScriptJay ASANAY"
			],
			'input' => "123 Igpenpay igpay atinlay ofway 123 on'tday opstay elievingbay inway ourselfyay eenquay avaScriptJay ASANAY",
			// XXX: this is currently treated as just a guess, so it doesn't
			// prevent pig latin from being double-encoded.
			'code' => 'en-x-piglatin'
		]
	];

	/** @var ReplacementMachine */
	private static $machine;

	public static function setUpBeforeClass(): void {
		$lang = LanguageConverter::loadLanguage(
			new MockEnv( [] ),
			new Bcp47CodeValue( 'en' )
		);
		self::$machine = $lang->getConverter()->getMachine();
	}

	public static function tearDownAfterClass(): void {
		self::$machine = null;
	}

	/**
	 * @covers \Wikimedia\LangConv\FST
	 * @dataProvider provideEn
	 */
	public function testEn( string $title, array $output, string $input, ?string $invertCode ) {
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

	public function provideEn() {
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
		return $variantCode === "en" ? "en-x-piglatin" : "en";
	}
}

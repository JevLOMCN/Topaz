<?php

namespace MediaWiki\Extension\DiscussionTools\Tests;

use MediaWiki\Extension\DiscussionTools\LanguageData;
use MediaWiki\MainConfigNames;
use MediaWiki\MediaWikiServices;

/**
 * @covers \MediaWiki\Extension\DiscussionTools\LanguageData
 */
class LanguageDataTest extends IntegrationTestCase {

	/**
	 * @dataProvider provideLocalData
	 */
	public function testGetLocalData( string $langCode, array $config, string $expectedPath ): void {
		$config += [
			MainConfigNames::LanguageCode => $langCode,
			MainConfigNames::UsePigLatinVariant => false,
			MainConfigNames::TranslateNumerals => true,
			MainConfigNames::Localtimezone => 'UTC',
		];
		$this->overrideConfigValues( $config );

		$expectedData = static::getJson( $expectedPath );

		$services = MediaWikiServices::getInstance();
		$languageData = new LanguageData(
			$services->getMainConfig(),
			$services->getContentLanguage(),
			$services->getLanguageConverterFactory(),
			$services->getSpecialPageFactory()
		);

		$data = $languageData->getLocalData();

		// Optionally write updated content to the JSON files
		if ( getenv( 'DISCUSSIONTOOLS_OVERWRITE_TESTS' ) ) {
			static::overwriteJsonFile( $expectedPath, $data );
		}

		static::assertEquals( $expectedData, $data );
	}

	public static function provideLocalData(): array {
		return [
			// Boring
			[ 'en', [], '../cases/datatest-en.json' ],
			// Has language variants (T259818)
			[ 'sr', [], '../cases/datatest-sr.json' ],
			// Has localised digits (T261706)
			[ 'ckb', [], '../cases/datatest-ckb.json' ],
			// Has unusual timezone abbreviation (T265500)
			[ 'th', [ MainConfigNames::Localtimezone => 'Asia/Bangkok' ], '../cases/datatest-th.json' ],
			// Special page alias with underscores (T327021)
			[ 'hu', [], '../cases/datatest-hu.json' ],
		];
	}

}

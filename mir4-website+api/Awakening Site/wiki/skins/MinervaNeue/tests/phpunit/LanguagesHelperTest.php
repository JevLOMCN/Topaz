<?php

namespace MediaWiki\Minerva;

use ILanguageConverter;
use Language;
use MediaWiki\Languages\LanguageConverterFactory;
use MediaWiki\Output\OutputPage;
use MediaWiki\Title\Title;
use MediaWikiIntegrationTestCase;

/**
 * @package Tests\MediaWiki\Minerva
 * @group MinervaNeue
 * @coversDefaultClass \MediaWiki\Minerva\LanguagesHelper
 */
class LanguagesHelperTest extends MediaWikiIntegrationTestCase {

	/**
	 * Build test Output object
	 * @param array $langLinks
	 * @return OutputPage
	 */
	private function getOutput( array $langLinks ) {
		$out = $this->createMock( OutputPage::class );
		$out->expects( $this->once() )
			->method( 'getLanguageLinks' )
			->willReturn( $langLinks );

		return $out;
	}

	/**
	 * Build test LanguageConverterFactory object
	 * @param bool $hasVariants
	 * @return LanguageConverterFactory
	 */
	private function getLanguageConverterFactory( $hasVariants ) {
		$langConv = $this->createMock( ILanguageConverter::class );
		$langConv->method( 'hasVariants' )->willReturn( $hasVariants );
		$langConvFactory = $this->createMock( LanguageConverterFactory::class );
		$langConvFactory->method( 'getLanguageConverter' )->willReturn( $langConv );

		return $langConvFactory;
	}

	/**
	 * Build test Title object
	 * @return Title
	 */
	private function getTitle() {
		$languageMock = $this->createMock( Language::class );
		$title = $this->createMock( Title::class );
		$title->method( 'getPageLanguage' )
			->willReturn( $languageMock );

		return $title;
	}

	/**
	 * @dataProvider provideDoesTitleHasLanguagesOrVariants
	 * @param bool $hasVariants
	 * @param array $langLinks
	 * @param bool $expected
	 * @covers ::__construct
	 * @covers ::doesTitleHasLanguagesOrVariants
	 */
	public function testDoesTitleHasLanguagesOrVariants( bool $hasVariants, array $langLinks, bool $expected ) {
		$helper = new LanguagesHelper(
			$this->getLanguageConverterFactory( $hasVariants )
		);

		$this->assertSame( $expected, $helper->doesTitleHasLanguagesOrVariants(
			$this->getOutput( $langLinks ),
			$this->getTitle()
		) );
	}

	public static function provideDoesTitleHasLanguagesOrVariants() {
		return [
			[ false, [ 'pl:StronaTestowa', 'en:TestPage' ], true ],
			[ true, [ 'pl:StronaTestowa', 'en:TestPage' ], true ],
			[ false, [], false ],
			[ true, [], true ],
		];
	}
}

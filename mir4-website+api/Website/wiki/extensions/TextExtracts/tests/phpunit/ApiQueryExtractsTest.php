<?php

namespace TextExtracts\Test;

use ILanguageConverter;
use MediaWiki\Config\ConfigFactory;
use MediaWiki\Config\HashConfig;
use MediaWiki\Languages\LanguageConverterFactory;
use MediaWiki\Title\Title;
use MediaWikiCoversValidator;
use TextExtracts\ApiQueryExtracts;
use Wikimedia\LightweightObjectStore\ExpirationAwareness;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \TextExtracts\ApiQueryExtracts
 * @group TextExtracts
 *
 * @license GPL-2.0-or-later
 */
class ApiQueryExtractsTest extends \MediaWikiIntegrationTestCase {
	use MediaWikiCoversValidator;

	private function newInstance() {
		$config = new HashConfig( [
			'ParserCacheExpireTime' => ExpirationAwareness::TTL_INDEFINITE,
		] );

		$configFactory = $this->createMock( ConfigFactory::class );
		$configFactory->method( 'makeConfig' )
			->with( 'textextracts' )
			->willReturn( $config );

		$cache = new \WANObjectCache( [ 'cache' => new \HashBagOStuff() ] );

		$context = $this->createMock( \IContextSource::class );
		$context->method( 'getConfig' )
			->willReturn( $config );
		$context->method( 'msg' )
			->willReturnCallback( function ( $key, ...$params ) {
				$msg = $this->createMock( \Message::class );
				$msg->method( 'text' )->willReturn( "($key)" );
				return $msg;
			} );

		$main = $this->createMock( \ApiMain::class );
		$main->expects( $this->once() )
			->method( 'getContext' )
			->willReturn( $context );

		$query = $this->createMock( \ApiQuery::class );
		$query->expects( $this->once() )
			->method( 'getMain' )
			->willReturn( $main );

		$langConv = $this->createMock( ILanguageConverter::class );
		$langConv->method( 'getPreferredVariant' )
			->willReturn( 'en' );
		$langConvFactory = $this->createMock( LanguageConverterFactory::class );
		$langConvFactory->method( 'getLanguageConverter' )
			->willReturn( $langConv );

		return new ApiQueryExtracts(
			$query,
			'',
			$configFactory,
			$cache,
			$langConvFactory,
			$this->getServiceContainer()->getWikiPageFactory()
		);
	}

	public function testMemCacheHelpers() {
		$title = $this->createMock( Title::class );
		$title->method( 'getPageLanguage' )
			->willReturn( $this->createMock( \Language::class ) );

		$page = $this->createMock( \WikiPage::class );
		$page->method( 'getTitle' )
			->willReturn( $title );
		$page->method( 'getId' )
			->willReturn( 123 );
		$page->method( 'getTouched' )
			->willReturn( '20010101000000' );

		$text = 'Text to cache';

		/** @var ApiQueryExtracts $instance */
		$instance = TestingAccessWrapper::newFromObject( $this->newInstance() );
		// Default param values for this API module
		$instance->params = [ 'intro' => false, 'plaintext' => false ];

		$this->assertFalse( $instance->getFromCache( $page, false ), 'is not cached yet' );

		$instance->setCache( $page, $text );
		$instance->cache->clearProcessCache();
		$this->assertSame( $text, $instance->getFromCache( $page, false ) );
	}

	public function testSelfDocumentation() {
		/** @var ApiQueryExtracts $instance */
		$instance = TestingAccessWrapper::newFromObject( $this->newInstance() );

		$this->assertIsString( $instance->getCacheMode( [] ) );
		$this->assertNotEmpty( $instance->getExamplesMessages() );
		$this->assertIsString( $instance->getHelpUrls() );

		$params = $instance->getAllowedParams();
		$this->assertIsArray( $params );

		$this->assertSame( 1, $params['chars'][\ApiBase::PARAM_MIN] );
		$this->assertSame( 1200, $params['chars'][\ApiBase::PARAM_MAX] );

		$this->assertSame( 20, $params['limit'][\ApiBase::PARAM_DFLT] );
		$this->assertSame( 'limit', $params['limit'][\ApiBase::PARAM_TYPE] );
		$this->assertSame( 1, $params['limit'][\ApiBase::PARAM_MIN] );
		$this->assertSame( 20, $params['limit'][\ApiBase::PARAM_MAX] );
		$this->assertSame( 20, $params['limit'][\ApiBase::PARAM_MAX2] );
	}

	/**
	 * @dataProvider provideFirstSectionsToExtract
	 */
	public function testGetFirstSection( $text, $isPlainText, $expected ) {
		/** @var ApiQueryExtracts $instance */
		$instance = TestingAccessWrapper::newFromObject( $this->newInstance() );

		$this->assertSame( $expected, $instance->getFirstSection( $text, $isPlainText ) );
	}

	public static function provideFirstSectionsToExtract() {
		return [
			'Plain text match' => [
				"First\nsection \1\2... \1\2...",
				true,
				"First\nsection ",
			],
			'Plain text without a match' => [
				'Example\1\2...',
				true,
				'Example\1\2...',
			],

			'HTML match' => [
				"First\nsection <h1>...<h2>...",
				false,
				"First\nsection ",
			],
			'HTML without a match' => [
				'Example <h11>...',
				false,
				'Example <h11>...',
			],
			'__TOC__ before intro (HTML)' => [
				'<h2 id="mw-toc-heading">Contents</h2>Intro<h2>Actual heading</h2>...',
				false,
				'<h2 id="mw-toc-heading">Contents</h2>Intro',
			],
			'__TOC__ before intro (plaintext)' => [
				"\1\2_\2\1<h2 id=\"mw-toc-heading\">Contents</h2>Intro\1\2_\2\1<h2>Actual heading</h2>...",
				true,
				"\1\2_\2\1<h2 id=\"mw-toc-heading\">Contents</h2>Intro",
			],
		];
	}

	/**
	 * @dataProvider provideTextsToTruncate
	 */
	public function testTruncate( $text, array $params, $expected ) {
		/** @var ApiQueryExtracts $instance */
		$instance = TestingAccessWrapper::newFromObject( $this->newInstance() );
		$instance->params = $params + [ 'chars' => null, 'sentences' => null, 'plaintext' => true ];

		$this->assertSame( $expected, $instance->truncate( $text ) );
	}

	public static function provideTextsToTruncate() {
		return [
			[ '', [], '' ],
			[ 'abc', [], 'abc' ],
			[
				'abc',
				[ 'chars' => 1 ],
				'abc'
			],
			[
				'abc',
				[ 'chars' => 1, 'plaintext' => false ],
				'abc'
			],
			[
				'abc',
				[ 'sentences' => 1 ],
				'abc'
			],
			[
				'abc abc. xyz xyz.',
				[ 'chars' => 1 ],
				'abc(ellipsis)'
			],
			[
				'abc abc. xyz xyz.',
				[ 'sentences' => 1 ],
				'abc abc.'
			],
			[
				'abc abc. xyz xyz.',
				[ 'chars' => 1000 ],
				'abc abc. xyz xyz.'
			],
			[
				'abc abc. xyz xyz.',
				[ 'chars' => 1000, 'plaintext' => false ],
				'abc abc. xyz xyz.'
			],
			[
				'abc abc. xyz xyz.',
				[ 'sentences' => 10 ],
				'abc abc. xyz xyz.'
			],
		];
	}

	/**
	 * @dataProvider provideSectionsToFormat
	 */
	public function testDoSections( $text, $format, $expected ) {
		/** @var ApiQueryExtracts $instance */
		$instance = TestingAccessWrapper::newFromObject( $this->newInstance() );
		$instance->params = [ 'sectionformat' => $format ];

		$this->assertSame( $expected, $instance->doSections( $text ) );
	}

	public static function provideSectionsToFormat() {
		$level = 3;
		$marker = "\1\2$level\2\1";

		return [
			'Raw' => [
				"$marker Headline\t\nNext line",
				'raw',
				"$marker Headline\t\nNext line",
			],
			'Wiki text' => [
				"$marker Headline\t\nNext line",
				'wiki',
				"\n=== Headline ===\nNext line",
			],
			'Plain text' => [
				"$marker Headline\t\nNext line",
				'plain',
				"\nHeadline\nNext line",
			],

			'Multiple matches' => [
				"{$marker}First\n{$marker}Second",
				'wiki',
				"\n=== First ===\n\n=== Second ===",
			],
		];
	}

}

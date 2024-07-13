<?php

namespace Test\Parsoid;

use Wikimedia\Bcp47Code\Bcp47CodeValue;
use Wikimedia\Parsoid\Core\PageBundle;
use Wikimedia\Parsoid\Mocks\MockDataAccess;
use Wikimedia\Parsoid\Mocks\MockMetrics;
use Wikimedia\Parsoid\Mocks\MockPageConfig;
use Wikimedia\Parsoid\Mocks\MockPageContent;
use Wikimedia\Parsoid\Mocks\MockSiteConfig;
use Wikimedia\Parsoid\Parsoid;
use Wikimedia\Parsoid\Utils\PHPUtils;

/**
 * Test the entrypoint to the library.
 *
 * @coversDefaultClass \Wikimedia\Parsoid\Parsoid
 */
class ParsoidTest extends \PHPUnit\Framework\TestCase {

	private static $defaultContentVersion = Parsoid::AVAILABLE_VERSIONS[0];

	/**
	 * @covers ::wikitext2html
	 * @dataProvider provideWt2Html
	 */
	public function testWt2Html( string $wt, string $expected, array $parserOpts = [] ) {
		$opts = [];

		$siteConfig = new MockSiteConfig( $opts );
		$dataAccess = new MockDataAccess( $siteConfig, $opts );
		$parsoid = new Parsoid( $siteConfig, $dataAccess );

		$pageContent = new MockPageContent( [ 'main' => $wt ] );
		$pageConfig = new MockPageConfig( $siteConfig, $opts, $pageContent );
		$out = $parsoid->wikitext2html( $pageConfig, $parserOpts );
		if ( !empty( $parserOpts['pageBundle'] ) ) {
			$this->assertTrue( $out instanceof PageBundle );
			$this->assertEquals( $expected, $out->html );
		} else {
			$this->assertEquals( $expected, $out );
		}
	}

	public function provideWt2Html(): array {
		return [
			[
				"'''hi ho'''",
				"<p data-parsoid='{\"dsr\":[0,11,0,0]}'><b data-parsoid='{\"dsr\":[0,11,3,3]}'>hi ho</b></p>",
				[
					'body_only' => true,
					'wrapSections' => false,
				]
			],
			[
				"'''hi ho'''",
				"<p id=\"mwAQ\"><b id=\"mwAg\">hi ho</b></p>",
				[
					'body_only' => true,
					'wrapSections' => false,
					'pageBundle' => true,
				]
			],
		];
	}

	/**
	 * @covers ::wikitext2lint
	 * @dataProvider provideWt2Lint
	 */
	public function testWt2Lint( $wt, $expected, $parserOpts = [] ) {
		$opts = [
			'linting' => true,
		];

		$siteConfig = new MockSiteConfig( $opts );
		$dataAccess = new MockDataAccess( $siteConfig, $opts );
		$parsoid = new Parsoid( $siteConfig, $dataAccess );

		$pageContent = new MockPageContent( [ 'main' => $wt ] );
		$pageConfig = new MockPageConfig( $siteConfig, $opts, $pageContent );
		$lint = $parsoid->wikitext2lint( $pageConfig, $parserOpts );
		$this->assertEquals( $expected, $lint );
	}

	public function provideWt2Lint() {
		return [
			[
				"[http://google.com This is [[Google]]'s search page]",
				[
					[
						'type' => 'wikilink-in-extlink',
						'dsr' => [ 0, 52, 19, 1 ],
						'params' => [],
						'templateInfo' => null,
					]
				]
			]
		];
	}

	/**
	 * @covers ::html2wikitext
	 * @dataProvider provideHtml2Wt
	 */
	public function testHtml2Wt( $input, $expected, $parserOpts = [] ) {
		$opts = [];

		$siteConfig = new MockSiteConfig( $opts );
		$dataAccess = new MockDataAccess( $siteConfig, $opts );
		$parsoid = new Parsoid( $siteConfig, $dataAccess );

		$pageContent = new MockPageContent( [ 'main' => '' ] );
		$pageConfig = new MockPageConfig( $siteConfig, $opts, $pageContent );
		$wt = $parsoid->html2wikitext( $pageConfig, $input, $parserOpts );
		$this->assertEquals( $expected, $wt );
	}

	public function provideHtml2Wt() {
		return [
			[
				"<pre>hi</pre>\n<div>ho</div>",
				" hi\n<div>ho</div>"
			],
			[
				"<h2></h2>",
				''
			]
		];
	}

	/**
	 * @covers ::pb2pb
	 * @dataProvider providePb2Pb
	 */
	public function testPb2Pb( $update, $input, $expected, $testOpts = [] ) {
		$opts = [];

		$siteConfig = new MockSiteConfig( $opts );
		$dataAccess = new MockDataAccess( $siteConfig, $opts );
		$parsoid = new Parsoid( $siteConfig, $dataAccess );

		$pageContent = new MockPageContent( [ 'main' => $testOpts['pageContent'] ?? '' ] );
		$pageConfig = new MockPageConfig( $siteConfig, [
			'pageLanguage' => $testOpts['pageLanguage'] ?? 'en'
		], $pageContent );
		$pb = new PageBundle(
			$input['html'],
			PHPUtils::jsonDecode( $input['parsoid'] ?? 'null' ),
			PHPUtils::jsonDecode( $input['mw'] ?? 'null' ),
			$input['version'] ?? null,
			$input['headers'] ?? null,
			$input['contentmodel'] ?? null
		);
		$out = $parsoid->pb2pb( $pageConfig, $update, $pb, $testOpts );
		$this->assertTrue( $out instanceof PageBundle );
		$this->assertEquals( $expected['html'], $out->html );
		$this->assertEquals( $expected['parsoid'] ?? 'null', PHPUtils::jsonEncode( $out->parsoid ) );
		$this->assertEquals( $expected['mw'] ?? 'null', PHPUtils::jsonEncode( $out->mw ) );
		$this->assertEquals( $expected['version'] ?? null, $out->version );
		if ( isset( $expected['headers'] ) ) {
			$this->assertEquals( $expected['headers'] ?? null, $out->headers );
		}
	}

	public function providePb2Pb() {
		// phpcs:disable Generic.Files.LineLength.TooLong
		return [
			[
				'convertoffsets',
				[
					'html' => '<p id="mwAA">La Luna è l\'unico satellite naturale della Terra</p><p id="mwAQ">La Luna è l\'unico satellite naturale della Terra</p>',
					'parsoid' => '{"counter":2,"ids":{"mwAA":{"dsr":[0,49,0,0]},"mwAQ":{"dsr":[51,100,0,0]}},"offsetType":"byte"}',
					'mw' => null,
				],
				[
					'html' => '<p id="mwAA">La Luna è l\'unico satellite naturale della Terra</p><p id="mwAQ">La Luna è l\'unico satellite naturale della Terra</p>',
					'parsoid' => '{"counter":2,"ids":{"mwAA":{"dsr":[0,48,0,0]},"mwAQ":{"dsr":[50,98,0,0]}},"offsetType":"ucs2"}',
					'mw' => '{"ids":[]}',
					'version' => self::$defaultContentVersion,
				],
				[
					'inputOffsetType' => 'byte',
					'outputOffsetType' => 'ucs2',
					// Note the accented e char that is a multi-byte char.
					// Without the pageContent property, offset conversion will not work.
					'pageContent' => "La Luna è l'unico satellite naturale della Terra\n\nLa Luna è l'unico satellite naturale della Terra",
					'body_only' => true
				]
			],
			[
				'convertoffsets',
				[
					'html' => '<p id="mwAA">La Luna è l\'unico satellite naturale della Terra</p><p id="mwAQ">La Luna è l\'unico satellite naturale della Terra</p>',
					'parsoid' => '{"counter":2,"ids":{"mwAA":{"dsr":[0,48,0,0]},"mwAQ":{"dsr":[50,98,0,0]}},"offsetType":"ucs2"}',
					'mw' => null,
				],
				[
					'html' => '<p id="mwAA">La Luna è l\'unico satellite naturale della Terra</p><p id="mwAQ">La Luna è l\'unico satellite naturale della Terra</p>',
					'parsoid' => '{"counter":2,"ids":{"mwAA":{"dsr":[0,49,0,0]},"mwAQ":{"dsr":[51,100,0,0]}},"offsetType":"byte"}',
					'mw' => '{"ids":[]}',
					'version' => self::$defaultContentVersion,
				],
				[
					'inputOffsetType' => 'ucs2',
					'outputOffsetType' => 'byte',
					'pageContent' => "La Luna è l'unico satellite naturale della Terra\n\nLa Luna è l'unico satellite naturale della Terra",
					'body_only' => true
				]
			],
			[
				'redlinks',
				[
					'html' => '<p><a rel="mw:WikiLink" href="./Special:Version" title="Special:Version">Special:Version</a> <a rel="mw:WikiLink" href="./Doesnotexist?action=edit&amp;redlink=1" typeof="mw:LocalizedAttrs" title="Doesnotexist" class="new" data-mw-i18n=\'{"title":{"lang":"x-page","key":"red-link-title","params":["Doesnotexist"]}}\'>Doesnotexist</a> <a rel="mw:WikiLink" href="./Redirected" title="Redirected">Redirected</a></p>',
					'parsoid' => null,
					'mw' => null,
				],
				[
					'html' => '<p><a rel="mw:WikiLink" href="./Special:Version" title="Special:Version">Special:Version</a> <a rel="mw:WikiLink" href="./Doesnotexist?action=edit&amp;redlink=1" typeof="mw:LocalizedAttrs" title="Doesnotexist" class="new" data-mw-i18n=\'{"title":{"lang":"x-page","key":"red-link-title","params":["Doesnotexist"]}}\'>Doesnotexist</a> <a rel="mw:WikiLink" href="./Redirected" title="Redirected" class="mw-redirect">Redirected</a></p>',
					'parsoid' => '{"counter":-1,"ids":[],"offsetType":"byte"}',
					'mw' => '{"ids":[]}',
					'version' => self::$defaultContentVersion,
				],
				[
					'body_only' => true,
				]
			],
			[
				'redlinks',
				[
					'html' => '<body id="mwAA" lang="en" class="mw-content-ltr sitedir-ltr ltr mw-body-content parsoid-body mediawiki mw-parser-output" dir="ltr"><p id="mwAQ"><a rel="mw:WikiLink" href="./Not_an_article?action=edit&amp;redlink=1" id="mwAg" typeof="mw:LocalizedAttrs" class="new" title="Not an article" data-mw-i18n=\'{"title":{"lang":"x-page","key":"red-link-title","params":["Not an article"]}}\'>abcd</a></p>' . "\n" . '</body>',
					'parsoid' => '{"counter":2,"ids":{"mwAA":{"dsr":[0,24,0,0]},"mwAQ":{"dsr":[0,23,0,0]},"mwAg":{"stx":"piped","a":{"href":"./Not_an_article"},"sa":{"href":"Not an article"},"dsr":[0,23,17,2]}},"offsetType":"byte"}',
					'mw' => '{"ids":[]}',
				],
				[
					'html' => '<p id="mwAQ"><a rel="mw:WikiLink" href="./Not_an_article?action=edit&amp;redlink=1" id="mwAg" typeof="mw:LocalizedAttrs" title="Not an article" class="new" data-mw-i18n=\'{"title":{"lang":"x-page","key":"red-link-title","params":["Not an article"]}}\'>abcd</a></p>' . "\n",
					'parsoid' => '{"counter":-1,"ids":{"mwAA":{"dsr":[0,24,0,0]},"mwAQ":{"dsr":[0,23,0,0]},"mwAg":{"stx":"piped","a":{"href":"./Not_an_article"},"sa":{"href":"Not an article"},"dsr":[0,23,17,2]}},"offsetType":"byte"}',
					'mw' => '{"ids":[]}',
					'version' => self::$defaultContentVersion,
				],
				[
					'body_only' => true,
				],
			],
			// Note that id attributes are preserved, even if no data-parsoid
			// is provided.
			[
				'redlinks',
				[
					'html' => '<body id="mwAA" lang="en" class="mw-content-ltr sitedir-ltr ltr mw-body-content parsoid-body mediawiki mw-parser-output" dir="ltr"><p id="mwAQ"><a id="mwAg" href="./Not_an_article?action=edit&amp;redlink=1" title="Not an article">abcd</a></p></body>',
					'parsoid' => null,
					'mw' => null,
				],
				[
					'html' => '<p id="mwAQ"><a id="mwAg" href="./Not_an_article?action=edit&amp;redlink=1" title="Not an article">abcd</a></p>',
					'parsoid' => '{"counter":-1,"ids":[],"offsetType":"byte"}',
					'mw' => '{"ids":[]}',
					'version' => self::$defaultContentVersion,
				],
				[
					'body_only' => true,
				],
			],
			// Language Variant conversion endpoint
			[
				'variant',
				[
					'html' => '<p>абвг abcd x</p>',
					'parsoid' => null,
					'mw' => null,
				],
				[
					'html' => '<p data-mw-variant-lang="sr-ec">abvg <span typeof="mw:LanguageVariant" data-mw-variant=\'{"twoway":[{"l":"sr-ec","t":"abcd"},{"l":"sr-el","t":"abcd"}],"rt":true}\'>abcd</span> x</p>',
					'parsoid' => '{"counter":-1,"ids":[],"offsetType":"byte"}',
					'mw' => '{"ids":[]}',
					'version' => self::$defaultContentVersion,
				],
				[
					'body_only' => true,
					'pageLanguage' => new Bcp47CodeValue( 'sr' ),
					'variant' => [
						'wikitext' => new Bcp47CodeValue( 'sr-Cyrl' ),
						'html' => new Bcp47CodeValue( 'sr-Latn' ),
					]
				]
			],
			[
				'variant',
				[
					'html' => '<p>абвг abcd x</p>',
					'parsoid' => null,
					'mw' => null,
				],
				[
					'html' => '<p data-mw-variant-lang="sr-el"><span typeof="mw:LanguageVariant" data-mw-variant=\'{"twoway":[{"l":"sr-el","t":"абвг"},{"l":"sr-ec","t":"абвг"}],"rt":true}\'>абвг</span> абцд x</p>',
					'parsoid' => '{"counter":-1,"ids":[],"offsetType":"byte"}',
					'mw' => '{"ids":[]}',
					'version' => self::$defaultContentVersion,
				],
				[
					'body_only' => true,
					'pageLanguage' => new Bcp47CodeValue( 'sr' ),
					'variant' => [
						'wikitext' => new Bcp47CodeValue( 'sr-Latn' ),
						'html' => new Bcp47CodeValue( 'sr-Cyrl' ),
					]
				]
			],
			[
				'variant',
				[
					'html' => '<body id="mwAA" lang="en" class="mw-content-ltr sitedir-ltr ltr mw-body-content parsoid-body mediawiki mw-parser-output" dir="ltr"><p id="mwAQ"><b id="mwAg">abcd</b></p></body>',
					'parsoid' => '{"counter":2,"ids":{"mwAA":{"dsr":[0,11,0,0]},"mwAQ":{"dsr":[0,10,0,0]},"mwAg":{"dsr":[0,10,3,3]}},"offsetType":"byte"}',
					'mw' => '{"ids":[]}',
				],
				[
					'html' => '<p id="mwAQ" data-mw-variant-lang="sr-el"><b id="mwAg">абцд</b></p>',
					'parsoid' => '{"counter":-1,"ids":{"mwAA":{"dsr":[0,11,0,0]},"mwAQ":{"dsr":[0,10,0,0]},"mwAg":{"dsr":[0,10,3,3]}},"offsetType":"byte"}',
					'mw' => '{"ids":[]}',
					'version' => self::$defaultContentVersion,
				],
				[
					'body_only' => true,
					'pageLanguage' => new Bcp47CodeValue( 'sr' ),
					'variant' => [
						'wikitext' => new Bcp47CodeValue( 'sr-Latn' ),
						'html' => new Bcp47CodeValue( 'sr-Cyrl' ),
					]
				]
			],
			// Note that id attributes are preserved, even if no data-parsoid
			// is provided.
			[
				'variant',
				[
					'html' => '<body id="mwAA" lang="en" class="mw-content-ltr sitedir-ltr ltr mw-body-content parsoid-body mediawiki mw-parser-output" dir="ltr"><p id="mwAQ"><b id="mwAg">abcd</b></p></body>',
					'parsoid' => null,
					'mw' => null,
				],
				[
					'html' => '<p id="mwAQ" data-mw-variant-lang="sr-el"><b id="mwAg">абцд</b></p>',
					'parsoid' => '{"counter":-1,"ids":[],"offsetType":"byte"}',
					'mw' => '{"ids":[]}',
					'version' => self::$defaultContentVersion,
				],
				[
					'body_only' => true,
					'pageLanguage' => new Bcp47CodeValue( 'sr' ),
					'variant' => [
						'wikitext' => new Bcp47CodeValue( 'sr-Latn' ),
						'html' => new Bcp47CodeValue( 'sr-Cyrl' ),
					]
				]
			],
		];
		// phpcs:enable Generic.Files.LineLength.TooLong
	}

	/**
	 * @covers ::implementsLanguageConversionBcp47
	 * @dataProvider provideImplementsLanguageConversionBcp47
	 */
	public function testImplementsLanguageConversionBcp47( string $htmlVariantCode, $expected ) {
		$opts = [];

		$siteConfig = new MockSiteConfig( $opts );
		$dataAccess = new MockDataAccess( $siteConfig, $opts );
		$parsoid = new Parsoid( $siteConfig, $dataAccess );

		$pageContent = new MockPageContent( [ 'main' => '' ] );
		$pageConfig = new MockPageConfig( $siteConfig, $opts, $pageContent );

		$actual = $parsoid->implementsLanguageConversionBcp47( $pageConfig, new Bcp47CodeValue( $htmlVariantCode ) );
		$this->assertEquals( $expected, $actual );
	}

	public function provideImplementsLanguageConversionBcp47() {
		yield 'Variant conversion is implemented for en-x-piglatin' => [
			'en-x-piglatin', true
		];

		yield 'Variant conversion is not implemented for kk-latn' => [
			'kk-latn', false
		];
	}

	/**
	 * @covers ::recordParseMetrics
	 */
	public function testParseMetrics() {
		$opts = [];
		$wt = "testing '''123'''";

		$siteConfig = new MockSiteConfig( $opts );
		$dataAccess = new MockDataAccess( $siteConfig, $opts );
		$parsoid = new Parsoid( $siteConfig, $dataAccess );

		$pageContent = new MockPageContent( [ 'main' => $wt ] );
		$pageConfig = new MockPageConfig( $siteConfig, $opts, $pageContent );
		$parsoid->wikitext2html( $pageConfig );

		$metrics = $siteConfig->metrics();
		$this->assertInstanceOf( MockMetrics::class, $metrics );
		$log = $metrics->log;
		$this->assertContains(
			[ 'timing', 'entry.wt2html.pageWithOldid.size.input', 17 ],
			$log
		);
	}

	/**
	 * @covers ::recordSerializationMetrics
	 */
	public function testSerializationMetrics() {
		$opts = [];
		$html = "<p>hiho</p>";

		$siteConfig = new MockSiteConfig( $opts );
		$dataAccess = new MockDataAccess( $siteConfig, $opts );
		$parsoid = new Parsoid( $siteConfig, $dataAccess );

		$pageContent = new MockPageContent( [ 'main' => '' ] );
		$pageConfig = new MockPageConfig( $siteConfig, $opts, $pageContent );

		$wt = $parsoid->html2wikitext( $pageConfig, $html );
		$this->assertEquals( 'hiho', $wt );

		$metrics = $siteConfig->metrics();
		$this->assertInstanceOf( MockMetrics::class, $metrics );
		$log = $metrics->log;
		$this->assertCount( 7, $log );

		$this->assertEquals(
			[ 'timing', 'entry.html2wt.size.input', 11 ],
			$log[3]
		);
		$this->assertEquals(
			[ 'timing', 'entry.html2wt.size.output', 4 ],
			$log[5]
		);
	}

}

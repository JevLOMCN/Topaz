<?php

namespace Test\Parsoid\Config\Api;

use Wikimedia\Bcp47Code\Bcp47CodeValue;
use Wikimedia\Parsoid\Config\Api\SiteConfig;

/**
 * @covers \Wikimedia\Parsoid\Config\Api\SiteConfig
 */
class SiteConfigTest extends \PHPUnit\Framework\TestCase {

	private static $siteConfig;

	protected function getSiteConfig(): SiteConfig {
		if ( self::$siteConfig === null ) {
			$helper = new TestApiHelper( $this, 'siteinfo' );
			self::$siteConfig = new SiteConfig( $helper, [] );
		}

		return self::$siteConfig;
	}

	public function testAllowedExternalImagePrefixes() {
		$this->assertSame(
			[],
			$this->getSiteConfig()->allowedExternalImagePrefixes()
		);
	}

	public function testBaseURI() {
		$this->assertSame(
			'//en.wikipedia.org/wiki/',
			$this->getSiteConfig()->baseURI()
		);
	}

	public function testRelativeLinkPrefix() {
		$this->assertSame(
			'./',
			$this->getSiteConfig()->relativeLinkPrefix()
		);
	}

	/** @dataProvider bswPagePropProvider */
	public function testBswPagePropRegexp( string $pageProp, bool $caseSensitive ) {
		$re = $this->getSiteConfig()->bswPagePropRegexp();
		$this->assertSame( 1, preg_match( $re, "mw:PageProp/$pageProp" ) );
		$this->assertSame( 1, preg_match( $re, "foo mw:PageProp/$pageProp  bar" ) );
		// Check case sensitivity
		$lower = strtolower( $pageProp );
		$expected = $lower === $pageProp ? 1 : ( $caseSensitive ? 0 : 1 );
		$this->assertSame( $expected, preg_match( $re, "mw:PageProp/$lower" ) );
		$this->assertSame( $expected, preg_match( $re, " mw:PageProp/$lower " ) );

		$upper = strtoupper( $pageProp );
		$expected = ( $upper === $pageProp ) ? 1 : ( $caseSensitive ? 0 : 1 );
		$this->assertSame( $expected, preg_match( $re, "mw:PageProp/$upper" ) );
		$this->assertSame( $expected, preg_match( $re, " mw:PageProp/$upper " ) );
	}

	public function bswPagePropProvider() {
		return [
			// Case sensitive
			[ 'NOGLOBAL', true ],
			[ 'DISAMBIG', true ],
			[ 'NEWSECTIONLINK', true ],
			[ 'NONEWSECTIONLINK', true ],
			[ 'HIDDENCAT', true ],
			[ 'EXPECTUNUSEDCATEGORY', true ],
			[ 'INDEX', true ],
			[ 'NOINDEX', true ],
			[ 'STATICREDIRECT', true ],

			// Case insensitive
			[ 'NoTOC', false ],
			[ 'NoGallery', false ],
			[ 'ForceTOC', false ],
			[ 'ToC', false ],
			[ 'NoEditSection', false ],
			[ 'NoTitleConvert', false ],
			[ 'NoTC', false ],
			[ 'NoContentConvert', false ],
			[ 'NoCC', false ],
		];
	}

	public function testCanonicalNamespaceId() {
		$this->assertSame( 5, $this->getSiteConfig()->canonicalNamespaceId( 'Project talk' ) );
		$this->assertNull( $this->getSiteConfig()->canonicalNamespaceId( 'Wikipedia talk' ) );
	}

	public function testNamespaceId() {
		$this->assertSame( 0, $this->getSiteConfig()->namespaceId( '' ) );
		$this->assertSame( 5, $this->getSiteConfig()->namespaceId( 'Wikipedia talk' ) );
		$this->assertSame( 5, $this->getSiteConfig()->namespaceId( 'WiKiPeDiA_TaLk' ) );
		$this->assertNull( $this->getSiteConfig()->namespaceId( 'Foobar' ) );
	}

	public function testNamespaceName() {
		$this->assertSame( '', $this->getSiteConfig()->namespaceName( 0 ) );
		$this->assertSame( 'Wikipedia talk', $this->getSiteConfig()->namespaceName( 5 ) );
		$this->assertNull( $this->getSiteConfig()->namespaceName( 500 ) );
	}

	public function testNamespaceHasSubpages() {
		$this->assertSame( false, $this->getSiteConfig()->namespaceHasSubpages( 0 ) );
		$this->assertTrue( $this->getSiteConfig()->namespaceHasSubpages( 1 ) );
	}

	public function testNamespaceCase() {
		$this->assertSame( 'first-letter', $this->getSiteConfig()->namespaceCase( 0 ) );
		$this->assertSame( 'first-letter', $this->getSiteConfig()->namespaceCase( 1 ) );
	}

	public function testSpecialPageLocalName() {
		$this->assertSame(
			'RecentChanges', $this->getSiteConfig()->specialPageLocalName( 'recentchanges' )
		);
		$this->assertSame(
			'RecentChangesLinked', $this->getSiteConfig()->specialPageLocalName( 'RelatedChanges' )
		);
		$this->assertSame(
			null, $this->getSiteConfig()->specialPageLocalName( 'FooBar' )
		);
	}

	public function testNamespaceIsTalk() {
		$this->assertSame( false, $this->getSiteConfig()->namespaceIsTalk( -1 ) );
		$this->assertSame( false, $this->getSiteConfig()->namespaceIsTalk( 0 ) );
		$this->assertTrue( $this->getSiteConfig()->namespaceIsTalk( 1 ) );
	}

	public function testInterwikiMagic() {
		$this->assertTrue(
			$this->getSiteConfig()->interwikiMagic()
		);
	}

	public function testInterwikiMap() {
		$ret = $this->getSiteConfig()->interwikiMap();
		$this->assertIsArray( $ret );
		$this->assertSame(
			[
				'prefix' => 'zh-cn',
				'local' => true,
				'language' => true,
				'url' => 'https://zh.wikipedia.org/wiki/$1',
			],
			$ret['zh-cn']
		);
	}

	public function testIwp() {
		$this->assertSame(
			'enwiki',
			$this->getSiteConfig()->iwp()
		);
	}

	public function testLegalTitleChars() {
		$this->assertSame(
			' %!"$&\'()*,\-.\/0-9:;=?@A-Z\\\\^_`a-z~\x80-\xFF+',
			$this->getSiteConfig()->legalTitleChars()
		);
	}

	public function testLinkPrefixRegex() {
		$this->assertSame(
			null,
			$this->getSiteConfig()->linkPrefixRegex()
		);
	}

	public function testLinkTrailRegex() {
		$this->assertSame(
			'/^([a-z]+)/sD',
			$this->getSiteConfig()->linkTrailRegex()
		);
	}

	public function testLangBcp47() {
		$this->assertEqualsIgnoringCase(
			'en',
			$this->getSiteConfig()->langBcp47()->toBcp47Code()
		);
	}

	public function testMainpage() {
		$this->assertSame(
			'Main Page',
			$this->getSiteConfig()->mainpage()
		);
	}

	public function testGetMWConfigValue() {
		$this->assertSame(
			true,
			$this->getSiteConfig()->getMWConfigValue( 'CiteResponsiveReferences' )
		);
		$this->assertSame(
			10,
			$this->getSiteConfig()->getMWConfigValue( 'CiteResponsiveReferencesThreshold' )
		);
		$this->assertSame(
			null,
			$this->getSiteConfig()->getMWConfigValue( 'CiteUnknownConfig' )
		);
	}

	public function testRtl() {
		$this->assertSame(
			false,
			$this->getSiteConfig()->rtl()
		);
	}

	public function testLangConverterEnabledBcp47() {
		$this->assertTrue( $this->getSiteConfig()->langConverterEnabledBcp47( new Bcp47CodeValue( 'zh' ) ) );
		$this->assertFalse( $this->getSiteConfig()->langConverterEnabledBcp47( new Bcp47CodeValue( 'de' ) ) );
	}

	public function testScript() {
		$this->assertSame(
			'/w/index.php',
			$this->getSiteConfig()->script()
		);
	}

	public function testScriptpath() {
		$this->assertSame(
			'/w',
			$this->getSiteConfig()->scriptpath()
		);
	}

	public function testServer() {
		$this->assertSame(
			'//en.wikipedia.org',
			$this->getSiteConfig()->server()
		);
	}

	public function testSolTransparentWikitextRegexp() {
		$this->assertSame(
			// phpcs:ignore Generic.Files.LineLength.TooLong
			'@^[ \t\n\r\0\x0b]*(?:(?:(?i:\#REDIRECT))[ \t\n\r\x0c]*(?::[ \t\n\r\x0c]*)?\[\[[^\]]+\]\])?(?:\[\[Category\:[^\]]*?\]\]|__(?:(?:NOGLOBAL|DISAMBIG|EXPECTUNUSEDCATEGORY|HIDDENCAT|INDEX|NEWSECTIONLINK|NOINDEX|NONEWSECTIONLINK|STATICREDIRECT)|(?i:FORCETOC|NOCONTENTCONVERT|NOCC|NOEDITSECTION|NOGALLERY|NOTITLECONVERT|NOTC|NOTOC|TOC))__|<!--(?>[\s\S]*?-->)|[ \t\n\r\0\x0b])*$@',
			$this->getSiteConfig()->solTransparentWikitextRegexp()
		);
	}

	public function testSolTransparentWikitextNoWsRegexp() {
		$this->assertSame(
			// phpcs:ignore Generic.Files.LineLength.TooLong
			'@((?:(?:(?i:\#REDIRECT))[ \t\n\r\x0c]*(?::[ \t\n\r\x0c]*)?\[\[[^\]]+\]\])?(?:\[\[Category\:[^\]]*?\]\]|__(?:(?:NOGLOBAL|DISAMBIG|EXPECTUNUSEDCATEGORY|HIDDENCAT|INDEX|NEWSECTIONLINK|NOINDEX|NONEWSECTIONLINK|STATICREDIRECT)|(?i:FORCETOC|NOCONTENTCONVERT|NOCC|NOEDITSECTION|NOGALLERY|NOTITLECONVERT|NOTC|NOTOC|TOC))__|<!--(?>[\s\S]*?-->))*)@',
			$this->getSiteConfig()->solTransparentWikitextNoWsRegexp()
		);
	}

	public function testTimezoneOffset() {
		$this->assertSame(
			0,
			$this->getSiteConfig()->timezoneOffset()
		);
	}

	public function testVariantsFor() {
		$ret = $this->getSiteConfig()->variantsFor( new Bcp47CodeValue( 'zh-hant-tw' ) );
		$this->assertIsArray( $ret );
		$this->assertEquals(
			[
				'base' => new Bcp47CodeValue( 'zh' ),
				'fallbacks' => [
					new Bcp47CodeValue( 'zh-Hant' ),
					new Bcp47CodeValue( 'zh-Hant-HK' ),
					new Bcp47CodeValue( 'zh-Hant-MO' ),
				],
			],
			$ret
		);
	}

	public function testWidthOption() {
		$this->assertSame(
			220,
			$this->getSiteConfig()->widthOption()
		);
	}

	public function testGetMagicWordForBehaviorSwitch() {
		$this->assertSame(
			'disambiguation',
			$this->getSiteConfig()->getMagicWordForBehaviorSwitch( '__DISAMBIG__' )
		);
	}

	public function testMwAliases() {
		$ret = $this->getSiteConfig()->mwAliases();
		$this->assertIsArray( $ret );
		$this->assertSame(
			[
				'DEFAULTSORT:',
				'DEFAULTSORTKEY:',
				'DEFAULTCATEGORYSORT:',
			],
			$ret['defaultsort'] ?? null
		);
	}

	public function testGetMagicWordForMediaOption() {
		$this->assertSame(
			'img_width',
			$this->getSiteConfig()->getMagicWordForMediaOption( '$1px' )
		);
	}

	public function testIsBehaviorSwitch() {
		$this->assertTrue( $this->getSiteConfig()->isBehaviorSwitch( '__TOC__' ) );
		$this->assertSame( false, $this->getSiteConfig()->isBehaviorSwitch( 'img_width' ) );
	}

	public function testGetMagicWordMatcher() {
		$this->assertSame(
			'/^(?:(?:SUBJECTPAGENAME|ARTICLEPAGENAME))$/D',
			$this->getSiteConfig()->getMagicWordMatcher( 'subjectpagename' )
		);
		$this->assertSame(
			'/^(?!)$/',
			$this->getSiteConfig()->getMagicWordMatcher( 'doesnotexist' )
		);
	}

	public function testGetMagicPatternMatcher() {
		$matcher = $this->getSiteConfig()->getParameterizedAliasMatcher(
			[ 'img_manualthumb', 'img_lossy', 'img_width', 'img_link' ] );

		// Basic tests
		$this->assertSame( [ 'k' => 'img_width', 'v' => '123' ], $matcher( '123px' ) );
		$this->assertSame( [ 'k' => 'img_lossy', 'v' => '123' ], $matcher( 'lossy=123' ) );
		$this->assertSame( [ 'k' => 'img_link', 'v' => 'http://example.com' ],
			$matcher( 'link=http://example.com' ) );

		// Test alias handling
		$this->assertSame( [ 'k' => 'img_manualthumb', 'v' => 'Foo.jpg' ],
			$matcher( 'thumbnail=Foo.jpg' ) ); // primary alias for img_manualthumb
		$this->assertSame( [ 'k' => 'img_manualthumb', 'v' => 'Foo.jpg' ],
			$matcher( 'thumb=Foo.jpg' ) ); // secondary alias for img_manualthumb

		// Tests partial matches of just the key without a value.
		// WikiLinkHandler use this method in this fashion.
		$this->assertSame( [ 'k' => 'img_link', 'v' => '' ], $matcher( 'link=' ) );

		// img_page isn't in the list of image options above
		$this->assertNull( $matcher( 'page=123' ) );
		// enlace is link in Spanish, but this alias isn't present in siteconfig data
		$this->assertNull( $matcher( 'enlace=123' ) );
	}

	public function testIsExtensionTag() {
		$this->assertTrue( $this->getSiteConfig()->isExtensionTag( 'pre' ) );
		$this->assertFalse( $this->getSiteConfig()->isExtensionTag( 'bogus' ) );
	}

	public function testGetExtensionTagNameMap() {
		$this->assertSame(
			[
				'pre' => true,
				'nowiki' => true,
				'gallery' => true,
				'indicator' => true,
				'langconvert' => true,
				'timeline' => true,
				'hiero' => true,
				'inputbox' => true,
				'imagemap' => true,
				'source' => true,
				'syntaxhighlight' => true,
				'poem' => true,
				'categorytree' => true,
				'score' => true,
				'templatestyles' => true,
				'templatedata' => true,
				'math' => true,
				'ce' => true,
				'chem' => true,
				'graph' => true,
				'maplink' => true,
				'mapframe' => true,
				'charinsert' => true,
				'ref' => true,
				'references' => true,
				'section' => true,
				'labeledsectiontransclusion' => true,
				'labeledsectiontransclusion/begin' => true,
				'labeledsectiontransclusion/end' => true
			],
			array_fill_keys( array_keys( $this->getSiteConfig()->getExtensionTagNameMap() ), true )
		);
	}

	public function testGetMaxTemplateDepth() {
		$this->assertSame( 40, $this->getSiteConfig()->getMaxTemplateDepth() );
	}

	public function testGetExtResourceURLPatternMatcher() {
		$matcher = $this->getSiteConfig()->getExtResourceURLPatternMatcher();
		$this->assertIsCallable( $matcher );
		$this->assertSame(
			[ 'ISBN', '12345' ],
			$matcher( 'Special:Booksources/12345' )
		);
	}

	public function testHasValidProtocol() {
		$this->assertSame(
			false,
			$this->getSiteConfig()->hasValidProtocol( 'foo bar http://www.example.com/xyz baz' )
		);
		$this->assertSame(
			true,
			$this->getSiteConfig()->hasValidProtocol( 'http://www.example.com/xyz baz' )
		);
	}

	public function testFindValidProtocol() {
		$this->assertSame(
			true,
			$this->getSiteConfig()->findValidProtocol( 'foo bar http://www.example.com/xyz baz' )
		);
	}

}

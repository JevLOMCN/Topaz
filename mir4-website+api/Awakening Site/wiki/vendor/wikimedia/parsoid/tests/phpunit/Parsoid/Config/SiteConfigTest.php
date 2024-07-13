<?php

namespace Test\Parsoid\Config;

use PHPUnit\Framework\MockObject\MockObject;
use Wikimedia\Bcp47Code\Bcp47CodeValue;
use Wikimedia\Parsoid\Config\SiteConfig;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Wikimedia\Parsoid\Config\SiteConfig
 */
class SiteConfigTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @param array $methods Subset of methods to mock.
	 *
	 * @return MockObject|SiteConfig
	 */
	private function getSiteConfig( array $methods = [] ) {
		return $this->getMockBuilder( SiteConfig::class )
			->onlyMethods( $methods )
			->getMockForAbstractClass();
	}

	public function testNamespaceIsTalk() {
		$siteConfig = $this->getSiteConfig();
		$this->assertFalse( $siteConfig->namespaceIsTalk( -2 ) );
		$this->assertFalse( $siteConfig->namespaceIsTalk( -1 ) );
		$this->assertFalse( $siteConfig->namespaceIsTalk( 0 ) );
		$this->assertTrue( $siteConfig->namespaceIsTalk( 1 ) );
		$this->assertFalse( $siteConfig->namespaceIsTalk( 2 ) );
		$this->assertTrue( $siteConfig->namespaceIsTalk( 3 ) );
	}

	public function testUcfirst() {
		$siteConfig = $this->getSiteConfig( [ 'langBcp47' ] );
		$siteConfig->method( 'langBcp47' )->willReturn( new Bcp47CodeValue( 'en' ) );

		$this->assertSame( 'Foo', $siteConfig->ucfirst( 'Foo' ) );
		$this->assertSame( 'Foo', $siteConfig->ucfirst( 'foo' ) );
		$this->assertSame( 'Ááá', $siteConfig->ucfirst( 'ááá' ) );
		$this->assertSame( 'Iii', $siteConfig->ucfirst( 'iii' ) );

		foreach ( [ 'tr', 'kaa', 'kk', 'az' ] as $lang ) {
			$siteConfig = $this->getSiteConfig( [ 'langBcp47' ] );
			$siteConfig->method( 'langBcp47' )->willReturn( new Bcp47CodeValue( $lang ) );
			$this->assertSame( 'İii', $siteConfig->ucfirst( 'iii' ), "Special logic for $lang" );
		}
	}

	/**
	 * @dataProvider provideMakeExtResourceURL
	 */
	public function testMakeExtResourceURL( array $match, string $href, string $content, string $expect ) {
		$siteConfig = $this->getSiteConfig();
		$this->assertSame( $expect, $siteConfig->makeExtResourceURL( $match, $href, $content ) );
	}

	public function provideMakeExtResourceURL(): array {
		return [
			[
				[ 'ISBN', '9780615720302' ], './Special:Booksources/9780615720302', 'ISBN 978-0615720302',
				'ISBN 978-0615720302'
			],
			[
				[ 'ISBN', '0596519796' ], './Special:Booksources/0596519796', "ISBN\u{00A0}0-596-51979-6",
				"ISBN\u{00A0}0-596-51979-6"
			],
			[
				[ 'ISBN', '221212466X' ], './Special:Booksources/221212466X', 'ISBN 2-212-12466-x',
				'ISBN 2-212-12466-x'
			],
			[
				[ 'ISBN', '9780615720302' ], './Special:Booksources/9780615720302', 'Working With MediaWiki',
				'[[Special:Booksources/9780615720302|Working With MediaWiki]]'
			],
			[
				[ 'RFC', '2324' ], 'https://tools.ietf.org/html/rfc2324', 'RFC 2324', 'RFC 2324'
			],
			[
				[ 'RFC', '2324' ], 'https://tools.ietf.org/html/rfc2324', 'HTCPCP',
				'[https://tools.ietf.org/html/rfc2324 HTCPCP]'
			],
		];
	}

	public function testMakeExtResourceURL_invalid() {
		$siteConfig = $this->getSiteConfig();
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( "Invalid match type 'Bogus'" );
		$siteConfig->makeExtResourceURL( [ 'Bogus', '0' ], './Bogus', 'Bogus 0' );
	}

	/**
	 * @dataProvider provideInterwikiMatcher
	 */
	public function testInterwikiMatcher( $href, $expect, $batchSize = null ) {
		$siteConfig = $this->getSiteConfig( [ 'interwikiMap' ] );
		if ( $batchSize !== null ) {
			TestingAccessWrapper::newFromObject( $siteConfig )->iwMatcherBatchSize = $batchSize;
		}
		$siteConfig->expects( $this->once() )->method( 'interwikiMap' )->willReturn( [
			'w' => [
				'prefix' => 'w',
				'local' => true,
				'localinterwiki' => true,
				'url' => 'https://en.wikipedia.org/wiki/$1',
			],
			'en' => [
				'prefix' => 'en',
				'local' => true,
				'language' => true,
				'localinterwiki' => true,
				'url' => 'https://en.wikipedia.org/wiki/$1',
			],
			'de' => [
				'prefix' => 'de',
				'local' => true,
				'language' => true,
				'url' => 'https://de.wikipedia.org/wiki/$1',
			],
			'iarchive' => [
				'prefix' => 'iarchive',
				'url' => 'https://archive.org/details/$1',
				'protorel' => true,
			],
			'example' => [
				'prefix' => 'example',
				'url' => '//example.org/$1',
			],
		] );

		$this->assertSame( $expect, $siteConfig->interwikiMatcher( $href ) );
		// Again, to test caching
		$this->assertSame( $expect, $siteConfig->interwikiMatcher( $href ) );
	}

	/**
	 * @dataProvider provideInterwikiMatcher
	 */
	public function testInterwikiMatcher_smallBatches( $href, $expect ) {
		$this->testInterwikiMatcher( $href, $expect, 1 );
	}

	public function provideInterwikiMatcher() {
		return [
			[ 'https://de.wikipedia.org/wiki/Foobar', [ ':de', 'Foobar' ] ],
			[ 'https://en.wikipedia.org/wiki/Foobar', [ ':en', 'Foobar' ] ], // not 'w'
			[ './w:Foobar', [ 'w', 'Foobar' ] ],
			[ 'de%3AFoobar', [ ':de', 'Foobar' ] ],

			// Protocol-relative handling
			[ 'https://archive.org/details/301works', [ 'iarchive', '301works' ] ],
			[ 'http://archive.org/details/301works', [ 'iarchive', '301works' ] ],
			[ '//archive.org/details/301works', [ 'iarchive', '301works' ] ],
			[ 'https://example.org/foobar', [ 'example', 'foobar' ] ],
			[ 'http://example.org/foobar', [ 'example', 'foobar' ] ],
			[ '//example.org/foobar', [ 'example', 'foobar' ] ],
			[ 'http://en.wikipedia.org/wiki/Foobar', null ],
			[ '//en.wikipedia.org/wiki/Foobar', null ],
		];
	}

	public function testInterwikiMatcher_tooManyPatterns() {
		$map = [];
		for ( $i = 0; $i < 100; $i++ ) {
			$map["x$i"] = [
				'prefix' => "x$i",
				'url' => "https://example.org/$i/$1",
			];
		}
		$map["l"] = [
			'prefix' => 'l',
			'language' => true,
			'url' => 'https://example.org/42/$1'
		];

		$siteConfig = $this->getSiteConfig( [ 'interwikiMap' ] );
		$siteConfig->method( 'interwikiMap' )->willReturn( $map );

		$this->assertSame(
			[ 'x76', 'Foobar' ],
			$siteConfig->interwikiMatcher( 'https://example.org/76/Foobar' )
		);
		$this->assertSame(
			[ ':l', 'Foobar' ],
			$siteConfig->interwikiMatcher( 'https://example.org/42/Foobar' )
		);
		$this->assertSame(
			null,
			$siteConfig->interwikiMatcher( 'https://example.org/9999999/Foobar' )
		);
	}

	/**
	 * @dataProvider provideProtocolMethods
	 */
	public function testProtocolMethods( $link, $expectHas, $expectFind ) {
		$siteConfig = $this->getSiteConfig( [ 'getProtocols' ] );
		$siteConfig->method( 'getProtocols' )->willReturn( [ 'https' ] );

		$this->assertSame( $expectHas, $siteConfig->hasValidProtocol( $link ) );
		$this->assertSame( $expectFind, $siteConfig->findValidProtocol( $link ) );
	}

	public function provideProtocolMethods() {
		return [
			[ 'http://wikipedia.org', false, false ],
			[ 'https://wikipedia.org', true, true ],
			[ 'ftp://ftp.something.net', false, false ],
			[ 'something http://wikipedia.org', false, false ],
			[ 'something https://wikipedia.org', false, true ],
			[ 'something ftp://ftp.something.net', false, false ],
			[ 'http://wikipedia.org https://wikipedia.org', false, true ],
			[ 'https://wikipedia.org http://wikipedia.org', true, true ],
		];
	}

	public function mockSpecialPageAlias( $specialPage ) {
		if ( $specialPage === 'Booksources' ) {
			return [ 'Booksources', 'BookSources' ]; // Mock value
		} else {
			throw new \BadMethodCallException( 'Not implemented' );
		}
	}

	/**
	 * @dataProvider provideGetResourceURLPatternMatcher
	 */
	public function testGetResourceURLPatternMatcher( $input, $res ) {
		// Alternatively, we can construct a MockSiteConfig object and use it since
		// it delegates all the work of the method under test to the base class.
		// But, this technique is probably more resilient in case MockSiteConfig changes.
		$siteConfig = $this->getSiteConfig( [ 'getSpecialNSAliases', 'getSpecialPageAliases' ] );
		$siteConfig->method( 'getSpecialNSAliases' )->willReturn( [ 'Special', 'special' ] );
		$siteConfig->method( 'getSpecialPageAliases' )->
			will( $this->returnCallBack( [ $this, 'mockSpecialPageAlias' ] ) );

		$matcher = $siteConfig->getExtResourceURLPatternMatcher();
		$this->assertSame( $res, $matcher( $input ) );
	}

	public function provideGetResourceURLPatternMatcher() {
		$isbnTests = [
			[ "Special:BookSources/1234567890X", [ 'ISBN', '1234567890X' ] ],
			[ "Special:Booksources/1234567890X", [ 'ISBN', '1234567890X' ] ],
			[ "special:BookSources/1234567890X", [ 'ISBN', '1234567890X' ] ],
			[ "special:Booksources/1234567890X", [ 'ISBN', '1234567890X' ] ],
			[ "Special:BookSources/1234567890x", [ 'ISBN', '1234567890x' ] ],
			[ "Special:BookSources/1234567890", [ 'ISBN', '1234567890' ] ],
			[ "../Special:BookSources/1234567890", [ 'ISBN', '1234567890' ] ],
			[ "../../Special:BookSources/1234567890", [ 'ISBN', '1234567890' ] ],
			[ "SPECIAL:BOOKSOURCES/1234567890", [ 'ISBN', '1234567890' ] ], // see the ?i flag
			[ "Special:BookSources/1234567890Y", false ],
			[ "special:boksources/1234567890", false ],
			[ "Notspecial:Booksources/1234567890", false ],
		];
		$pmidTests = [
			[ "//www.ncbi.nlm.nih.gov/pubmed/covid19?dopt=Abstract", [ 'PMID', 'covid19' ] ],
			[ "https://www.ncbi.nlm.nih.gov/pubmed/covid19?dopt=Abstract", [ 'PMID', 'covid19' ] ],
			[ "http://www.ncbi.nlm.nih.gov/pubmed/covid19?dopt=Abstract", [ 'PMID', 'covid19' ] ],
			[ "http://www.ncbi.nlm.nih.gov/pubmed/covid19", false ],
			// FIXME T257629: Strange that our code treats "foobar://" as "foobar:" + "//"
			[ "foobar://www.ncbi.nlm.nih.gov/pubmed/covid19?dopt=Abstract", [ 'PMID', 'covid19' ] ],
			[ "http://www.ncbi.nlm.nih.gov/pubmed/covid19?dopt=Abstract&something_more", false ],
		];
		$rfcTests = [
			[ "//tools.ietf.org/html/rfc1234", [ 'RFC', '1234' ] ],
			[ "https://tools.ietf.org/html/rfc1234", [ 'RFC', '1234' ] ],
			[ "http://tools.ietf.org/html/rfc1234", [ 'RFC', '1234' ] ],
			// FIXME T257629: Strange that our code accepts RFCs with "_" and doesn't have more validity checking
			// but, magic links are on the way out anyway.
			[ "http://tools.ietf.org/html/rfc_1234", [ 'RFC', '_1234' ] ],
			// FIXME T257629: Strange that our code treats "foobar://" as "foobar:" + "//"
			[ "foobar://tools.ietf.org/html/rfc1234", [ 'RFC', '1234' ] ],
			[ "http://tools.ietf.org/html/RFC1234", false ],
			[ "http://tools.ietf.org/json/rfc1234", false ],
		];

		return array_merge( $isbnTests, $pmidTests, $rfcTests );
	}

	private function setupMagicWordTestConfig(): SiteConfig {
		$mws = [
			"img_lossy"     => [ true, "lossy=$1" ],
			"numberofwikis" => [ false, "numberofwikis" ], // variable
			"lcfirst"       => [ false, "LCFIRST:" ], // is a no-hash function hook
			"expr"          => [ false, "expr" ], // function hook with valid hashed version
			"noglobal"      => [ true, "__NOGLOBAL__" ],
			"defaultsort"   => [ true, "DEFAULTSORT:", "DEFAULTSORTKEY:", "DEFAULTCATEGORYSORT:" ],
		];
		// computed based on magicword array above
		$functionSyns = [
			"0" => [ "#expr" => "expr", "lcfirst" => "lcfirst" ],
		];
		$vars = [ "numberofwikis" ];
		$siteConfig = $this->getSiteConfig( [
			'getMagicWords', 'getFunctionSynonyms', 'getVariableIDs'
		] );
		$siteConfig->method( 'getMagicWords' )->willReturn( $mws );
		$siteConfig->method( 'getFunctionSynonyms' )->willReturn( $functionSyns );
		$siteConfig->method( 'getVariableIDs' )->willReturn( $vars );

		return $siteConfig;
	}

	public function testMwAliases() {
		// FIXME: Given that Parsoid proxies {{..}} wikitext to core for expansion,
		// some of these tests don't mean a while lot right now. There are known
		// bugs in SiteConfig right now. (T257629)
		$siteConfig = $this->setupMagicWordTestConfig();
		$aliases = [
			// FIXME: Should the magic word code be de-duping the aliases array?
			"img_lossy"     => [ "lossy=$1" ],
			"numberofwikis" => [ "numberofwikis", "numberofwikis" ],
			"lcfirst"       => [ "LCFIRST:", "lcfirst:" ],
			"expr"          => [ "expr", "expr" ],
			"noglobal"      => [ "__NOGLOBAL__" ],
			"defaultsort"   => [ "DEFAULTSORT:", "DEFAULTSORTKEY:", "DEFAULTCATEGORYSORT:" ],
		];
		$this->assertSame( $aliases, $siteConfig->mwAliases() );
	}

	/**
	 * @dataProvider provideGetMagicWordForFunctionHooks
	 */
	public function testGetMagicWordForFunctionHooks( $input, $res ) {
		$siteConfig = $this->setupMagicWordTestConfig();
		$this->assertSame( $res, $siteConfig->getMagicWordForFunctionHook( $input ) );
	}

	public function provideGetMagicWordForFunctionHooks() {
		return [
			[ "LCFIRST", "lcfirst" ],
			[ "lcfirst", "lcfirst" ],
			[ "#expr", "expr" ],
			[ "#EXPR", "expr" ],
			[ "expr", null ],
			[ "expr:", null ],
			[ "#expr:", null ],
			[ "lcfirst:", null ],
			[ "#lcfirst", null ],
		];
	}

	/**
	 * @dataProvider provideGetMagicWordForVariable
	 */
	public function testGetMagicWordForVariable( $input, $res ) {
		$siteConfig = $this->setupMagicWordTestConfig();
		$this->assertSame( $res, $siteConfig->getMagicWordForVariable( $input ) );
	}

	public function provideGetMagicWordForVariable() {
		return [
			[ "numberofwikis", "numberofwikis" ],
			[ "NUMBEROFWIKIS", null ],
			[ "numberofadmins", null ],
		];
	}

	/**
	 * @dataProvider provideLinkTrailRegex
	 */
	public function testLinkTrailRegex( $input, $res ) {
		$siteConfig = $this->getSiteConfig( [ 'linkTrail' ] );
		$siteConfig->method( 'linkTrail' )->willReturn( $input );
		$this->assertSame( $res, $siteConfig->linkTrailRegex() );
	}

	public function provideLinkTrailRegex() {
		return [
			[ '/^([a-z]+)(.*)$/sD', '/^([a-z]+)/sD' ], // enwiki
			[ '/^()(.*)$/sD', null ] // zhwiki
		];
	}
}

<?php

namespace Test\Parsoid\Utils;

use PHPUnit\Framework\MockObject\MockObject;
use Wikimedia\Bcp47Code\Bcp47CodeValue;
use Wikimedia\Parsoid\Mocks\MockSiteConfig;
use Wikimedia\Parsoid\Utils\Title;
use Wikimedia\Parsoid\Utils\TitleException;

/**
 * @coversDefaultClass \Wikimedia\Parsoid\Utils\Title
 */
class TitleTest extends \PHPUnit\Framework\TestCase {
	private static function newTitle( ...$args ) {
		// Access non-public constructor
		$classReflection = new \ReflectionClass( Title::class );
		$constructor = $classReflection->getConstructor();
		$constructor->setAccessible( true );
		$title = $classReflection->newInstanceWithoutConstructor();
		$constructor->invokeArgs( $title, $args );
		return $title;
	}

	/**
	 * @covers ::__construct
	 * @covers ::getKey
	 * @covers ::getPrefixedDBKey
	 * @covers ::getPrefixedText
	 * @covers ::getFragment
	 * @covers ::getNamespace
	 * @dataProvider provideBasics
	 */
	public function testBasics( $args, $key, $pKey, $pText, $fragment, $ns ) {
		$title = self::newTitle( '', ...$args );
		$this->assertSame( $key, $title->getKey() );
		$this->assertSame( $pKey, $title->getPrefixedDBKey() );
		$this->assertSame( $pText, $title->getPrefixedText() );
		$this->assertSame( $fragment, $title->getFragment() ?: null );
		$this->assertSame( $ns, $title->getNamespace() );

		// LinkTarget tests
		$this->assertSame( '', $title->getInterwiki() );
		$this->assertSame( $ns, $title->getNamespace() );
		$this->assertSame( $key, $title->getDBKey() );
		$this->assertSame( $fragment ?? '', $title->getFragment() );
	}

	public function provideBasics() {
		return [
			'Basic article' => [ [ 'Basic_page', 0, '' ], 'Basic_page', 'Basic_page', 'Basic page', null, 0 ],
			'User-namespace page' => [
				[ 'Basic_page', 2, 'User' ], 'Basic_page', 'User:Basic_page', 'User:Basic page', null, 2
			],
			'With fragment' => [
				[ 'Basic_page', 0, '', 'frag' ], 'Basic_page', 'Basic_page', 'Basic page', 'frag', 0
			],
		];
	}

	/**
	 * @param string $lang
	 *
	 * @return MockObject|MockSiteConfig
	 */
	private function getMockSiteConfig( string $lang = 'en' ) {
		$siteConfig = $this->getMockBuilder( MockSiteConfig::class )
			->setConstructorArgs( [ [] ] )
			->onlyMethods( [ 'langBcp47', 'namespaceCase', 'specialPageLocalName', 'interwikiMap' ] )
			->getMock();
		$siteConfig->method( 'namespaceCase' )->willReturnCallback( static function ( $ns ) {
			return $ns === 15 ? 'case-sensitive' : 'first-letter';
		} );
		$siteConfig->method( 'langBcp47' )->willReturn( new Bcp47CodeValue( $lang ) );
		$siteConfig->method( 'specialPageLocalName' )->willReturnCallback( static function ( $alias ) {
			if ( $alias === 'DoesNotExist' ) {
				return null;
			}
			return strtoupper( $alias );
		} );
		$siteConfig->method( 'interwikiMap' )->willReturn( [
			'remotetestiw' => [
				'prefix' => 'remotetestiw',
				'url' => 'http://example.com/$1',
			],
		] );

		return $siteConfig;
	}

	/**
	 * @covers ::newFromText
	 * @dataProvider provideNewFromText
	 */
	public function testNewFromText( $args, $key, $ns, $fragment, $lang = null, $interwiki = '', $rawKey = null ) {
		array_splice( $args, 1, 0, [ $this->getMockSiteConfig( $lang ?? 'en' ) ] );
		$title = Title::newFromText( ...$args );
		$this->assertSame( $key, $title->getKey() );
		$this->assertSame( $ns, $title->getNamespace() );
		$this->assertSame( $fragment, $title->getFragment() ?: null );

		// LinkTarget tests
		$this->assertSame( $interwiki, $title->getInterwiki() );
		$this->assertSame( $ns, $title->getNamespace() );
		$this->assertSame( $rawKey ?? $key, $title->getDBKey() );
		$this->assertSame( $fragment ?? '', $title->getFragment() );
	}

	public function provideNewFromText() {
		$x255 = str_repeat( 'X', 255 );
		$x512 = str_repeat( 'X', 512 );
		$poo63 = str_repeat( '💩', 63 );
		return [
			'Basic article' => [ [ 'Basic page' ], 'Basic_page', 0, null ],
			'User-namespace page' => [ [ 'User:Foo bar' ], 'Foo_bar', 2, null ],
			'Default NS' => [ [ 'Foo bar', 2 ], 'Foo_bar', 2, null ],
			'Default NS, overridden' => [ [ 'File:Foo bar', 2 ], 'Foo_bar', 6, null ],
			'Default NS, overridden to main' => [ [ ':Foo bar', 2 ], 'Foo_bar', 0, null ],
			'Default NS, overridden to not-main' => [ [ ':File:Foo bar', 2 ], 'Foo_bar', 6, null ],
			'With fragment' => [ [ 'User:Basic page#frag#ment' ], 'Basic_page', 2, 'frag#ment' ],
			'Should normalize fragment' => [ [ 'User:Basic page#frag ment' ], 'Basic_page', 2, 'frag_ment' ],

			'Capitalization' => [ [ 'UsEr:foo Bar' ], 'Foo_Bar', 2, null ],
			'Capitalization, case-sensitive' => [ [ 'foo Bar', 15 ], 'foo_Bar', 15, null ],

			'Trim whitespace' => [ [ ' _ Basic _ page _ ' ], 'Basic_page', 0, null ],
			'Trim whitespace with namespace and fragment' => [
				[ ' _ User _ : _ Basic page _ #__ fragment _ ' ], 'Basic_page', 2, '_fragment'
			],
			'Replace whitespace' => [ [ "Bas\u{200E}ic\u{00A0}page" ], 'Basic_page', 0, null ],

			'IPv4 sanitization (1)' => [ [ 'User:192.0.2.0' ], '192.0.2.0', 2, null ],
			'IPv4 sanitization (2)' => [ [ 'User:000.000.020.000' ], '0.0.20.0', 2, null ],
			'IPv4 sanitization (3)' => [ [ 'User_talk:000.000.020.000' ], '0.0.20.0', 3, null ],
			'IPv4 sanitization (4)' => [ [ 'File:000.000.020.000' ], '000.000.020.000', 6, null ],
			'IPv6 sanitization (1)' => [ [ 'User:2001:db8::' ], '2001:DB8:0:0:0:0:0:0', 2, null ],
			'IPv6 sanitization (2)' => [
				[ 'User:2001:0db8::0:000:0010:000' ], '2001:DB8:0:0:0:0:10:0', 2, null
			],
			'IPv6 sanitization (::)' => [ [ 'User:::' ], '0:0:0:0:0:0:0:0', 2, null ],
			'IPv6 sanitization (::1)' => [ [ 'User:::1' ], '0:0:0:0:0:0:0:1', 2, null ],

			'Special page normalization (1)' => [ [ 'Special:FooBar' ], 'FOOBAR', -1, null ],
			'Special page normalization (2)' => [ [ 'Special:FooBar/baz' ], 'FOOBAR/baz', -1, null ],
			'Special page normalization (3)' => [ [ 'Special:DoesNotExist' ], 'DoesNotExist', -1, null ],

			'Allowed percent-sign' => [ [ '100%' ], '100%', 0, null ],
			'Allowed percent-sign (2)' => [ [ '100%ok' ], '100%ok', 0, null ],
			'Allowed ampersand' => [ [ 'foo & bar; ok' ], 'Foo_&_bar;_ok', 0, null ],
			'Allowed periods (1)' => [ [ 'foo . bar' ], 'Foo_._bar', 0, null ],
			'Allowed periods (2)' => [ [ 'foo ../. ./.. bar' ], 'Foo_../._./.._bar', 0, null ],
			'Long title' => [ [ "User talk:$x255" ], $x255, 3, null ],
			'Long title (Unicode)' => [ [ "User talk:$poo63" ], $poo63, 3, null ],
			'Long special title' => [ [ "Special:$x512" ], $x512, -1, null ],

			// == More tests copied from the JS module ==
			[ [ 'Sandbox' ], 'Sandbox', 0, null ],
			[ [ 'A "B"' ], 'A_"B"', 0, null ],
			[ [ 'A \'B\'' ], 'A_\'B\'', 0, null ],
			[ [ '.com' ], '.com', 0, null ],
			[ [ '~' ], '~', 0, null ],
			// Parsoid had historically differentiated between '' and '#'
			// as a title, but core does not distinguish between these two
			// cases of an "empty' title.
			[ [ '#' ], '', 0, null ],
			[ [ 'Test#Abc' ], 'Test', 0, 'Abc' ],
			[ [ '"' ], '"', 0, null ],
			[ [ '\'' ], '\'', 0, null ],
			[ [ 'Talk:Sandbox' ], 'Sandbox', 1, null ],
			[ [ 'Talk:Foo:Sandbox' ], 'Foo:Sandbox', 1, null ],
			[ [ 'File:Example.svg' ], 'Example.svg', 6, null ],
			[ [ 'File_talk:Example.svg' ], 'Example.svg', 7, null ],
			[ [ 'Foo/.../Sandbox' ], 'Foo/.../Sandbox', 0, null ],
			[ [ 'Sandbox/...' ], 'Sandbox/...', 0, null ],
			[ [ 'A~~' ], 'A~~', 0, null ],
			[ [ ':A' ], 'A', 0, null ],
			[ [ '-' ], '-', 0, null ],
			[ [ 'aũ' ], 'Aũ', 0, null ],
			[
				[ '"Believing_Women"_in_Islam._Unreading_Patriarchal_Interpretations_of_the_Qur\\\'ān' ],
				'"Believing_Women"_in_Islam._Unreading_Patriarchal_Interpretations_of_the_Qur\\\'ān', 0, null
			],

			[ [ 'Test' ], 'Test', 0, null ],
			[ [ ':Test' ], 'Test', 0, null ],
			[ [ ': Test' ], 'Test', 0, null ],
			[ [ ':_Test_' ], 'Test', 0, null ],
			[ [ 'Test 123  456   789' ], 'Test_123_456_789', 0, null ],
			[ [ '💩' ], '💩', 0, null ],
			[ [ 'Foo:bar' ], 'Foo:bar', 0, null ],
			[ [ 'Talk: foo' ], 'Foo', 1, null ],
			[ [ 'int:eger' ], 'Int:eger', 0, null ],
			[ [ 'WP:eger' ], 'Eger', 4, null ],
			[ [ 'X-Men (film series) #Gambit' ], 'X-Men_(film_series)', 0, 'Gambit' ],
			[ [ 'Foo _ bar' ], 'Foo_bar', 0, null ],
			// phpcs:ignore Generic.Files.LineLength.TooLong
			[ [ "Foo \u{00A0}\u{1680}\u{180E}\u{2000}\u{2001}\u{2002}\u{2003}\u{2004}\u{2005}\u{2006}\u{2007}\u{2008}\u{2009}\u{200A}\u{2028}\u{2029}\u{202F}\u{205F}\u{3000} bar" ], 'Foo_bar', 0, null ],
			[ [ "Foo\u{200E}\u{200F}\u{202A}\u{202B}\u{202C}\u{202D}\u{202E}bar" ], 'Foobar', 0, null ],
			// Special handling for `i` first character
			[ [ 'iTestTest' ], 'İTestTest', 0, null, 'tr' ],
			[ [ 'iTestTest' ], 'İTestTest', 0, null, 'az' ],
			[ [ 'iTestTest' ], 'İTestTest', 0, null, 'kk' ],
			[ [ 'iTestTest' ], 'İTestTest', 0, null, 'kaa' ],
			// User IP sanitizations
			[ [ 'User:::1' ], '0:0:0:0:0:0:0:1', 2, null ],
			[ [ 'User:0:0:0:0:0:0:0:1' ], '0:0:0:0:0:0:0:1', 2, null ],
			[ [ 'User:127.000.000.001' ], '127.0.0.1', 2, null ],
			[ [ 'User:0.0.0.0' ], '0.0.0.0', 2, null ],
			[ [ 'User:00.00.00.00' ], '0.0.0.0', 2, null ],
			[ [ 'User:000.000.000.000' ], '0.0.0.0', 2, null ],
			[ [ 'User:141.000.011.253' ], '141.0.11.253', 2, null ],
			[ [ 'User: 1.2.4.5' ], '1.2.4.5', 2, null ],
			[ [ 'User:01.02.04.05' ], '1.2.4.5', 2, null ],
			[ [ 'User:001.002.004.005' ], '1.2.4.5', 2, null ],
			[ [ 'User:010.0.000.1' ], '10.0.0.1', 2, null ],
			[ [ 'User:080.072.250.04' ], '80.72.250.4', 2, null ],
			[ [ 'User:Foo.1000.00' ], 'Foo.1000.00', 2, null ],
			[ [ 'User:Bar.01' ], 'Bar.01', 2, null ],
			[ [ 'User:Bar.010' ], 'Bar.010', 2, null ],
			[ [ 'User:cebc:2004:f::' ], 'CEBC:2004:F:0:0:0:0:0', 2, null ],
			[ [ 'User:::' ], '0:0:0:0:0:0:0:0', 2, null ],
			[ [ 'User:0:0:0:1::' ], '0:0:0:1:0:0:0:0', 2, null ],
			[ [ 'User:3f:535::e:fbb' ], '3F:535:0:0:0:0:E:FBB', 2, null ],
			[ [ 'User Talk:::1' ], '0:0:0:0:0:0:0:1', 3, null ],
			[ [ 'User_Talk:::1' ], '0:0:0:0:0:0:0:1', 3, null ],
			[ [ 'User_talk:::1' ], '0:0:0:0:0:0:0:1', 3, null ],
			[ [ 'User_talk:::1/24' ], '0:0:0:0:0:0:0:1/24', 3, null ],
			// Interwiki link, with prefix: note that we put these in the
			// main namespace, and the "dbkey" contains everything after the
			// interwiki prefix, including the "namespace"
			[ [ 'remotetestiw:User:Bar' ], 'remotetestiw:User:Bar', 0, null, null, 'remotetestiw', 'User:Bar' ],
			[ [ 'remotetestiw:User:Bar#Foo' ], 'remotetestiw:User:Bar', 0, 'Foo', null, 'remotetestiw', 'User:Bar' ],
			// remotetestiw in user (T329690)
			[ [ 'remotetestiw:', 2 ], 'remotetestiw:', 2, null, null, 'remotetestiw', '' ],
			// Colons in talk namespaces (T332903)
			// phpcs:ignore Generic.Files.LineLength.TooLong
			[ [ 'Talk:2024:Expressions of Interest/Wikimania 2024 Istanbul, Türkiye' ], '2024:Expressions_of_Interest/Wikimania_2024_Istanbul,_Türkiye', 1, null ],
		];
	}

	/**
	 * @covers ::newFromText
	 * @dataProvider provideNewFromText_errors
	 */
	public function testNewFromText_errors( $title, $type ) {
		try {
			Title::newFromText( $title, $this->getMockSiteConfig() );
			$this->fail( 'Expected exception not thrown' );
		} catch ( TitleException $ex ) {
			$this->assertSame( $type, $ex->type );
		}
	}

	public function provideNewFromText_errors() {
		$x256 = str_repeat( 'X', 256 );
		$x513 = str_repeat( 'X', 513 );
		$poo64 = str_repeat( '💩', 64 );
		return [
			'Bad UTF-8' => [ "foo\xa0bar", 'title-invalid-utf8' ],
			'Replacement character' => [ 'foo�bar', 'title-invalid-utf8' ],
			'Empty' => [ '', 'title-invalid-empty' ],
			'Only whitespace' => [ ' _ ', 'title-invalid-empty' ],
			'Invalid talk' => [ 'Talk:User:Foo', 'title-invalid-talk-namespace' ],
			'Invalid chars' => [ '<foo>', 'title-invalid-characters' ],
			'Invalid chars (percent-encoding)' => [ 'foo%26bar', 'title-invalid-characters' ],
			'Invalid chars (HTML entity)' => [ 'foo&amp;bar', 'title-invalid-characters' ],
			// Can never trigger these, the # is interpreted as starting the fragment
			// 'Invalid chars (HTML code)' => [ 'foo&#38;bar', 'title-invalid-characters' ],
			// 'Invalid chars (HTML hex code)' => [ 'foo&#x2A;bar', 'title-invalid-characters' ],
			'Relative path: .' => [ '.', 'title-invalid-relative' ],
			'Relative path: ..' => [ '..', 'title-invalid-relative' ],
			'Relative path: ./' => [ './foo', 'title-invalid-relative' ],
			'Relative path: ../' => [ '../foo', 'title-invalid-relative' ],
			'Relative path: /.' => [ 'foo/.', 'title-invalid-relative' ],
			'Relative path: /..' => [ 'foo/..', 'title-invalid-relative' ],
			'Relative path: /./' => [ 'foo/./bar', 'title-invalid-relative' ],
			'Relative path: /../' => [ 'foo/../bar', 'title-invalid-relative' ],
			'Tildes' => [ 'foo~~~bar', 'title-invalid-magic-tilde' ],
			'Too long' => [ $x256, 'title-invalid-too-long' ],
			'Too long (Unicode)' => [ $poo64, 'title-invalid-too-long' ],
			'Too long (special)' => [ "Special:$x513", 'title-invalid-too-long' ],
			'Empty except for namespace' => [ 'User:', 'title-invalid-empty' ],
			'Empty except for namespace+fragment' => [ 'User:#frag', 'title-invalid-empty' ],
			'Leading colon' => [ 'User::Hiho', 'title-invalid-leading-colon' ],

			// == More tests copied from the JS module ==
			[ ':', 'title-invalid-empty' ],
			[ '__  __', 'title-invalid-empty' ],
			[ '  __  ', 'title-invalid-empty' ],
			// Bad characters forbidden regardless of wgLegalTitleChars
			[ 'A [ B', 'title-invalid-characters' ],
			[ 'A ] B', 'title-invalid-characters' ],
			[ 'A { B', 'title-invalid-characters' ],
			[ 'A } B', 'title-invalid-characters' ],
			[ 'A < B', 'title-invalid-characters' ],
			[ 'A > B', 'title-invalid-characters' ],
			[ 'A | B', 'title-invalid-characters' ],
			// URL encoding
			[ 'A%20B', 'title-invalid-characters' ],
			[ 'A%23B', 'title-invalid-characters' ],
			[ 'A%2523B', 'title-invalid-characters' ],
			// XML/HTML character entity references
			// Note: Commented out because they are not marked invalid by the PHP test as
			// Title::newFromText runs Sanitizer::decodeCharReferencesAndNormalize first.
			// 'A &eacute; B',
			// Subject of NS_TALK does not roundtrip to NS_MAIN
			[ 'Talk:File:Example.svg', 'title-invalid-talk-namespace' ],
			// Directory navigation
			[ './Sandbox', 'title-invalid-relative' ],
			[ '../Sandbox', 'title-invalid-relative' ],
			[ 'Foo/./Sandbox', 'title-invalid-relative' ],
			[ 'Foo/../Sandbox', 'title-invalid-relative' ],
			[ 'Sandbox/.', 'title-invalid-relative' ],
			[ 'Sandbox/..', 'title-invalid-relative' ],
			// Tilde
			[ 'A ~~~ Name', 'title-invalid-magic-tilde' ],
			[ 'A ~~~~ Signature', 'title-invalid-magic-tilde' ],
			[ 'A ~~~~~ Timestamp', 'title-invalid-magic-tilde' ],
			// Namespace prefix without actual title
			[ 'Talk:', 'title-invalid-empty' ],
			[ 'Talk:#', 'title-invalid-empty' ],
			[ 'Category: ', 'title-invalid-empty' ],
			[ 'Category: #bar', 'title-invalid-empty' ],
		];
	}

}

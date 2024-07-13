<?php

namespace Test\Parsoid\Wt2Html\PP\Processors;

use PHPUnit\Framework\TestCase;
use Wikimedia\Parsoid\Core\DomSourceRange;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\Mocks\MockDataAccess;
use Wikimedia\Parsoid\Mocks\MockPageConfig;
use Wikimedia\Parsoid\Mocks\MockPageContent;
use Wikimedia\Parsoid\Mocks\MockSiteConfig;
use Wikimedia\Parsoid\Parsoid;
use Wikimedia\Parsoid\Utils\ContentUtils;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMDataUtils;
use Wikimedia\Parsoid\Utils\PHPUtils;

/**
 * based on tests/mocha/dsr.js
 * @covers \Wikimedia\Parsoid\Wt2Html\PP\Processors\ComputeDSR
 */
class ComputeDSRTest extends TestCase {

	public function validateSpec( string $wt, Element $doc, array $spec ): void {
		$elts = DOMCompat::querySelectorAll( $doc, $spec['selector'] );
		$this->assertCount( 1, $elts,
				"Cannot match selector \"{$spec['selector']}\" in doc " .
				ContentUtils::toXML( $doc ) );

		$dp = DOMDataUtils::getDataParsoid( $elts[0] );
		if ( $spec['dsrContent'] === null ) {
			$this->assertObjectNotHasProperty( 'dsr', $dp );
			return;
		}
		$this->assertObjectHasProperty( 'dsr', $dp );
		$dsr = $dp->dsr;
		$this->assertInstanceOf( DomSourceRange::class, $dsr );

		$this->assertSubstring( $spec['dsrContent'][0], $wt, $dsr->start, $dsr->length(),
			"all" );
		if ( $spec['dsrContent'][1] === null ) {
			$this->assertNull( $dsr->openWidth );
		} else {
			$this->assertSubstring( $spec['dsrContent'][1], $wt, $dsr->start, $dsr->openWidth,
				"open" );
		}
		if ( $spec['dsrContent'][2] === null ) {
			$this->assertNull( $dsr->closeWidth );
		} else {
			$this->assertSubstring( $spec['dsrContent'][2], $wt, $dsr->innerEnd(), $dsr->closeWidth,
				"close" );
		}
	}

	private function assertSubstring( $expected, $wt, $start, $length, $type ) {
		$this->assertIsInt( $start );
		$this->assertIsInt( $length );
		$this->assertGreaterThanOrEqual( 0, $start,
			"DSR $type start was less than zero" );
		if ( $length > 0 ) {
			$this->assertLessThan( strlen( $wt ), $start,
				"DSR $type start was greater than or equal to the input length" );
		}
		$this->assertGreaterThanOrEqual( 0, $length,
			"DSR $type length was less than zero" );
		$this->assertLessThanOrEqual( strlen( $wt ), $length,
			"DSR $type length was greater than the input length" );
		$this->assertSame( $expected, PHPUtils::safeSubstr( $wt, $start, $length ),
			"DSR $type did not match the expected string" );
	}

	/**
	 * For every test with a 'wt' property, provide the 'spec' property that is
	 * an array of DSR specs that need to be verified on the parsed output of 'wt'.
	 * Every spec should have:
	 *   selector: a CSS selector for picking a DOM node in the parsed output.
	 *   dsrContent: a 3-element array. The first element is the wikitext corresponding
	 *               to the wikitext substring between dsr[0]..dsr[1]. The second and
	 *               third elements are the opening/closing wikitext tag for that node.
	 * @dataProvider provideComputeDSR
	 * @param string $wt
	 * @param array $specs
	 */
	public function testComputeDSR( string $wt, array $specs ): void {
		$siteConfig = new MockSiteConfig( [] );
		$dataAccess = new MockDataAccess( $siteConfig, [] );
		$parsoid = new Parsoid( $siteConfig, $dataAccess );

		$content = new MockPageContent( [ 'main' => $wt ] );
		$pageConfig = new MockPageConfig( $siteConfig, [], $content );
		$html = $parsoid->wikitext2html( $pageConfig, [ "wrapSections" => false ] );

		$doc = ContentUtils::createAndLoadDocument( $html );

		foreach ( $specs as $spec ) {
			$this->validateSpec( $wt, DOMCompat::getBody( $doc ), $spec );
		}
	}

	public function provideComputeDSR(): array {
		return [
			// Paragraph wrapping
			'para 1' => [
				'wt' => 'a', // disable first test so we can get to the juicy failure immediately.
				'specs' => [
					[ 'selector' => 'body > p', 'dsrContent' => [ 'a', '', '' ] ]
				]
			],
			'para 2' => [
				'wt' => "a\n\nb",
				'specs' => [
					[ 'selector' => 'body > p:nth-child(1)', 'dsrContent' => [ 'a', '', '' ] ],
					[ 'selector' => 'body > p:nth-child(2)', 'dsrContent' => [ 'b', '', '' ] ]
				]
			],

			// Lists
			'list 1' => [
				'wt' => "*a\n*b",
				'specs' => [
					[ 'selector' => 'ul', 'dsrContent' => [ "*a\n*b", '', '' ] ],
					[ 'selector' => 'ul > li:nth-child(1)', 'dsrContent' => [ '*a', '*', '' ] ],
					[ 'selector' => 'ul > li:nth-child(2)', 'dsrContent' => [ '*b', '*', '' ] ]
				]
			],
			'list 2' => [
				'wt' => "*a\n**b\n***c\n*d",
				'specs' => [
					[ 'selector' => 'body > ul', 'dsrContent' => [ "*a\n**b\n***c\n*d", '', '' ] ],
					[ 'selector' => 'body > ul > li:first-child', 'dsrContent' => [ "*a\n**b\n***c", '*', '' ] ],
					[ 'selector' => 'body > ul > li:first-child > ul', 'dsrContent' => [ "**b\n***c", '', '' ] ],
					[
						'selector' => 'body > ul > li:first-child > ul > li:first-child',
						'dsrContent' => [ "**b\n***c", '**', '' ]
					],
					[
						'selector' => 'body > ul > li:first-child > ul > li:first-child > ul > li',
						'dsrContent' => [ '***c', '***', '' ]
					],
					[ 'selector' => 'body > ul > li:nth-child(2)', 'dsrContent' => [ '*d', '*', '' ] ]
				]
			],
			'list 3' => [
				// TODO: this test covers computeListEltWidth() but the return value does
				// not find its way into the DOM, so we're only really testing that it
				// doesn't throw.
				'wt' => '{{1x|** foo}}',
				'specs' => [
					[
						'selector' => 'body > ul > li',
						'dsrContent' => null
					],
				]
			],

			// Headings
			'heading 1' => [
				'wt' => "=A=\n==B==\n===C===\n====D====",
				'specs' => [
					[ 'selector' => 'body > h1', 'dsrContent' => [ '=A=', '=', '=' ] ],
					[ 'selector' => 'body > h2', 'dsrContent' => [ '==B==', '==', '==' ] ],
					[ 'selector' => 'body > h3', 'dsrContent' => [ '===C===', '===', '===' ] ],
					[ 'selector' => 'body > h4', 'dsrContent' => [ '====D====', '====', '====' ] ]
				]
			],
			'heading 2' => [
				'wt' => "=A New Use for the = Sign=\n==The == Operator==",
				'specs' => [
					[
						'selector' => 'body > h1',
						'dsrContent' => [ '=A New Use for the = Sign=', '=', '=' ]
					],
					[ 'selector' => 'body > h2', 'dsrContent' => [ '==The == Operator==', '==', '==' ] ]
				]
			],

			// Quotes
			'quotes 1' => [
				'wt' => "''a''\n'''b'''",
				'specs' => [
					[ 'selector' => 'p > i', 'dsrContent' => [ "''a''", "''", "''" ] ],
					[ 'selector' => 'p > b', 'dsrContent' => [ "'''b'''", "'''", "'''" ] ]
				]
			],

			// Tables
			'tables 1' => [
				'wt' => "{|\n|-\n|A\n|}",
				'specs' => [
					[ 'selector' => 'body > table', 'dsrContent' => [ "{|\n|-\n|A\n|}", '{|', '|}' ] ],
					[ 'selector' => 'body > table > tbody > tr', 'dsrContent' => [ "|-\n|A", '|-', '' ] ],
					[ 'selector' => 'body > table > tbody > tr > td', 'dsrContent' => [ '|A', '|', '' ] ]
				]
			],

			'tables 2' => [
				'wt' => "{|\n<span>A</span>\n|}",
				'specs' => [
					[
						'selector' => 'body > table',
						'dsrContent' => [ "{|\n<span>A</span>\n|}", '{|', '|}' ]
					],
					// FIXME: throws due to negative length
					/*
					[
						'selector' => 'body > p > span',
						'dsrContent' => [ "<span>A</span>", '<span>', '</span>' ]
					],
					*/
				]
			],

			'tables 3 mixed stx' => [
				'wt' => "{|\n|foo\n</table>",
				'specs' => [
					[
						'selector' => 'body > table',
						'dsrContent' => [ "{|\n|foo\n</table>", '{|', '</table>' ]
					],
					[
						'selector' => 'body > table > tbody',
						'dsrContent' => [ "|foo\n", '', '' ]
					],
					[
						'selector' => 'tr > td',
						'dsrContent' => [ "|foo", '|', '' ]
					]
				]
			],

			// Pre
			'pre 1' => [
				'wt' => " Preformatted text ",
				'specs' => [
					[ 'selector' => 'body > pre', 'dsrContent' => [ " Preformatted text ", ' ', '' ] ]
				]
			],
			// Regression test for robust DSR computation when PreHandler has a bug.
			// PreHandler swallows the category link into <pre> which creates an 1-char
			// difference in DSR offset by the time the <i> tag is encountered.
			// The endTSR value on the <i> tag should override the buggy computed value.
			'pre 2' => [
				'wt' => " ''a'' b\n[[Category:Foo]]",
				'specs' => [
					[ 'selector' => 'body > pre > i', 'dsrContent' => [ "''a''", "''", "''" ] ]
				]
			],

			// Elements
			'elt 1' => [
				'wt' => "<small>'''bold'''</small>",
				'specs' => [
					[
						'selector' => 'body > p',
						'dsrContent' => [ "<small>'''bold'''</small>", '', '' ]
					],
					[
						'selector' => 'body > p > small',
						'dsrContent' => [ "<small>'''bold'''</small>", '<small>', '</small>' ]
					]
				]
			],
			'elt 2 misnested' => [
				'wt' => '<b><i>A</b></i>',
				'specs' => [
					[
						'selector' => 'body > p > b',
						'dsrContent' => [ '<b><i>A</b></i>', '<b>', '</i>' ]
					],
					[
						'selector' => 'body > p > b > i',
						'dsrContent' => [ '<i>A', '<i>', '' ]
					],
				]
			],
			'elt 3 p-wrap node splitting' => [
				'wt' => '<b>a<div>b</div>c</b>',
				'specs' => [
					[
						'selector' => 'body > p:nth-of-type(1) > b',
						'dsrContent' => [ '<b>a', '<b>', '' ]
					],
					[
						'selector' => 'body > b',
						'dsrContent' => [ '<div>b</div>', '', '' ]
					],
					[
						'selector' => 'body > p:nth-of-type(2) > b',
						'dsrContent' => [ 'c</b>', '', '</b>' ]
					]
				]
			],
			'elt 4 AFE reconstruction' => [
				'wt' => '<div><p><b>a<p>b',
				'specs' => [
					[
						'selector' => 'body > div > p:nth-of-type(1) > b',
						'dsrContent' => [ '<b>a', '<b>', '' ]
					],
					[
						'selector' => 'body > div > p:nth-of-type(2) > b',
						'dsrContent' => [ 'b', '', '' ]
					],
				]
			],
			'elt 5 AAA' => [
				'wt' => '<div><b>a<div>b</b>c',
				'specs' => [
					[
						'selector' => 'body > div > b',
						'dsrContent' => [ '<b>a', '<b>', '' ]
					],
					[
						'selector' => 'body > div > div',
						'dsrContent' => [ '<div>b</b>c', '<div>', '' ]
					],
				]
			],
			'elt 6 misnested' => [
				'wt' => "<span>''''A</span><span>B</span>",
				'specs' => [
					[
						'selector' => 'body > p > span > b',
						'dsrContent' => [ "'''A", "'''", '' ]
					],
					[
						'selector' => 'body > p > b',
						'dsrContent' => [ '<span>B</span>', '', '' ]
					],
				]
			],

			// Mixed HTML and native-wikitext tags
			'html quote end tag' => [
				'wt' => "'''x</b>",
				'specs' => [
					[
						'selector' => 'body > p > b',
						'dsrContent' => [ "'''x</b>", "'''", '</b>' ]
					]
				],
			],

			// Links
			'link 1' => [
				'wt' => 'Foo https://en.wikipedia.org/ bar',
				'specs' => [
					[
						'selector' => 'a',
						'dsrContent' => [ 'https://en.wikipedia.org/', '', '' ]
					],
				]
			],
			'link 2' => [
				'wt' => 'http://example.com/?foo&#61;bar',
				'specs' => [
					[
						'selector' => 'a',
						'dsrContent' => [ 'http://example.com/?foo&#61;bar', '', '' ]
					]
				]
			],
			'link 3' => [
				'wt' => 'RFC  2616',
				'specs' => [
					[
						'selector' => 'a',
						'dsrContent' => [ 'RFC  2616', '', '' ]
					]
				]
			],
			'link 4' => [
				'wt' => '[[Foo]]',
				'specs' => [
					[
						'selector' => 'a',
						'dsrContent' => [ '[[Foo]]', '[[', ']]' ]
					]
				]
			],
			'link 5' => [
				'wt' => '[[Foo|bar]]',
				'specs' => [
					[
						'selector' => 'a',
						'dsrContent' => [ '[[Foo|bar]]', '[[Foo|', ']]' ]
					]
				]
			],
			'link 6' => [
				'wt' => '[https://en.wikipedia.org/ Wikipedia]',
				'specs' => [
					[
						'selector' => 'a',
						'dsrContent' => [ '[https://en.wikipedia.org/ Wikipedia]', '[https://en.wikipedia.org/ ', ']' ],
					]
				]
			],

			// xmlish extension tags
			'xmlish 1' => [
				'wt' => '<pre>Like rain gone wooden</pre>',
				'specs' => [
					[
						'selector' => 'pre[typeof="mw:Extension/pre"]',
						'dsrContent' => [ '<pre>Like rain gone wooden</pre>', '<pre>', '</pre>' ]
					]
				]
			],

			// Comments
			'comment 1' => [
				'wt' => '* <!--a--> b',
				'specs' => [
					[
						'selector' => 'li',
						'dsrContent' => [ '* <!--a--> b', '*', '' ]
					]
				]
			],
			'comment 2' => [
				// Misnesting is a way to get the comment length to appear in the output
				'wt' => '<div><b><i>AAA</b><!--BBB--></i></div>',
				'specs' => [
					[
						'selector' => 'body > div',
						'dsrContent' => [ '<div><b><i>AAA</b><!--BBB--></i></div>', '<div>', '</div>' ]
					]
				]
			],
			'unclosed comment' => [
				'wt' => '{{1x|<div>a}}\n\nb\n<!--\nx',
				'specs' => [
					[
						'selector' => 'body > div',
						'dsrContent' => [ '{{1x|<div>a}}\n\nb\n<!--\nx', null, '' ]
					]
				]
			],

			// Entities
			'entity 1' => [
				'wt' => '&mdash;',
				'specs' => [
					[
						'selector' => 'body > p > span',
						'dsrContent' => [ '&mdash;', null, null ]
					],
				]
			]
		];
	}
}

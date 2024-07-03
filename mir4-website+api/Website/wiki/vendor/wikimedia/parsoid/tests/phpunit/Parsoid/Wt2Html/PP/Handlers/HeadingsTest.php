<?php

namespace Test\Parsoid\Wt2Html\PP\Handlers;

use PHPUnit\Framework\TestCase;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\Mocks\MockDataAccess;
use Wikimedia\Parsoid\Mocks\MockPageConfig;
use Wikimedia\Parsoid\Mocks\MockPageContent;
use Wikimedia\Parsoid\Mocks\MockSiteConfig;
use Wikimedia\Parsoid\Parsoid;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMUtils;

/**
 * based on tests/mocha/heading.ids.js
 * @coversDefaultClass \Wikimedia\Parsoid\Wt2Html\PP\Handlers\Headings
 */
class HeadingsTest extends TestCase {

	public function validateId( string $name, string $heading, string $description, array $expectedIds,
								Element $doc ): void {
		$elts = DOMCompat::querySelectorAll( $doc, 'body > h1' );
		$this->assertSameSize( $expectedIds, $elts );

		foreach ( $expectedIds as $key => $id ) {
			$h = $elts[$key];
			$fallback = DOMCompat::querySelectorAll( $h, 'span[typeof="mw:FallbackId"]:empty' );
			if ( is_String( $id ) ) {
				$attrib = DOMCompat::getAttribute( $h, 'id' );
				$this->assertEquals( $id, $attrib, $name . $heading . $description . $id );
				$this->assertCount( 0, $fallback, $name . $description .
					' fallback should not be set.' );
			} else {
				$attrib = DOMCompat::getAttribute( $h, 'id' );
				$this->assertEquals( $attrib, $id[0], $name . $heading . $description . $id[0] );
				$this->assertCount( 1, $fallback, $name . $description .
					' fallback should be set to 1.' );
				$fallbackAttribute = DOMCompat::getAttribute( $fallback[0], 'id' );
				$this->assertEquals( $id[1], $fallbackAttribute, $name . $heading . $description . $id[1] );
			}
		}
	}

	public function runHeadingTest( string $name, array $test, string $description ): void {
		$heading = current( $test );
		array_shift( $test );
		$expectedIds = $test;

		$siteConfig = new MockSiteConfig( [] );
		$dataAccess = new MockDataAccess( $siteConfig, [] );
		$parsoid = new Parsoid( $siteConfig, $dataAccess );

		$content = new MockPageContent( [ 'main' => $heading ] );
		$pageConfig = new MockPageConfig( $siteConfig, [], $content );
		$html = $parsoid->wikitext2html( $pageConfig, [ "wrapSections" => false ] );

		$doc = DOMUtils::parseHTML( $html );
		$body = DOMCompat::getBody( $doc );

		$this->validateId( $name, $heading, $description, $expectedIds, $body );
	}

	/**
	 * @covers ::genAnchors
	 * @dataProvider provideSimpleHeadings
	 * @param array $simpleHeadings
	 */
	public function testSimpleHeadings( array $simpleHeadings ): void {
		$this->runHeadingTest( 'Simple Headings ', $simpleHeadings, ' should be valid for ' );
	}

	public function provideSimpleHeadings(): array {
		return [
			[ [ '=Test=', 'Test' ] ],
			[ [ '=Test 1 2 3=', 'Test_1_2_3' ] ],
			[ [ '=   Test   1 _2   3  =', 'Test_1_2_3' ] ]
		];
	}

	/**
	 * @covers ::genAnchors
	 * @dataProvider provideHeadingsWithWtChars
	 * @param array $headingsWithWtChars
	 */
	public function testHeadingsWithWtChars( array $headingsWithWtChars ): void {
		$this->runHeadingTest( 'Headings with ', $headingsWithWtChars,
			' wikitext chars should be ignored in ' );
	}

	public function provideHeadingsWithWtChars(): array {
		return [
			[ [ '=This is a [[Link]]=', 'This_is_a_Link' ] ],
			[ [ "=Some '''bold''' and '''italic''' text=", 'Some_bold_and_italic_text' ] ],
			[ [ "=Some {{1x|transclusion}} here=", 'Some_transclusion_here' ] ]
/* 	PORT-FIXME this is a bug in the template handler for nested templates
			[ [ "={{1x|a and ''b'' and [[c]] and {{1x|d}} and e}}=", "a_and_b_and_c_and_d_and_e" ] ],
*/
/*  PORT-FIXME this test depends on missing mock environment code
			[ [ "=Some {{convert|1|km}} here=", [ "Some_1_kilometre_(0.62_mi)_here",
				 "Some_1_kilometre_.280.62_mi.29_here" ] ] ]
*/
		];
	}

	/**
	 * @covers ::genAnchors
	 * @dataProvider provideHeadingsWithHTML
	 * @param array $headingsWithHTML
	 */
	public function testHeadingsWithHTML( array $headingsWithHTML ): void {
		$this->runHeadingTest( 'Headings with ', $headingsWithHTML,
			' HTML tags should be stripped in ' );
	}

	public function provideHeadingsWithHTML(): array {
		return [
			[ [ "=Some <span>html</span> <b>tags</b> here=", 'Some_html_tags_here' ] ],
			/* PHP parser output is a bit weird on a heading with these contents */
			[ [ "=a <div>b <span>c</span>d</div> e=", 'a_b_cd_e' ] ]
		];
	}

	/**
	 * @covers ::genAnchors
	 * @dataProvider provideHeadingsWithEntities
	 * @param array $headingsWithEntities
	 */
	public function testHeadingsWithEntities( array $headingsWithEntities ): void {
		$this->runHeadingTest( 'Headings with ', $headingsWithEntities,
			' Entities should be encoded ' );
	}

	public function provideHeadingsWithEntities(): array {
		return [
			[ [ '=Red, Blue, Yellow=', [ 'Red,_Blue,_Yellow', 'Red.2C_Blue.2C_Yellow' ] ] ],
			[ [ '=!@#$%^&*()=', [ '!@#$%^&*()', ".21.40.23.24.25.5E.26.2A.28.29" ] ] ],
			[ [ '=:=', ":" ] ]
		];
	}

	/**
	 * @covers ::genAnchors
	 * @dataProvider provideNonEnglishHeadings
	 * @param array $nonEnglishHeadings
	 */
	public function testNonEnglishHeadings( array $nonEnglishHeadings ): void {
		$this->runHeadingTest( 'Headings with non English characters ', $nonEnglishHeadings,
			' Ids should be valid ' );
	}

	public function provideNonEnglishHeadings(): array {
		return [
			[ [ "=Références=", [ "Références", "R.C3.A9f.C3.A9rences" ] ] ],
			// phpcs:ignore Generic.Files.LineLength.TooLong
			[ [ "=बादलों का वगीर्करण=", [ "बादलों_का_वगीर्करण",
				".E0.A4.AC.E0.A4.BE.E0.A4.A6.E0.A4.B2.E0.A5.8B.E0.A4.82_.E0.A4.95.E0.A4.BE_" .
				".E0.A4.B5.E0.A4.97.E0.A5.80.E0.A4.B0.E0.A5.8D.E0.A4.95.E0.A4.B0.E0.A4.A3" ] ] ]
		];
	}

	/**
	 * @covers ::genAnchors
	 * @dataProvider provideEdgeCases
	 * @param array $edgeCases
	 */
	public function testEdgeCases( array $edgeCases ): void {
		$this->runHeadingTest( 'Edge Case Tests ', $edgeCases, ' Ids should be valid ' );
	}

	public function provideEdgeCases(): array {
		return [
			[ [ "=a=\n=a=", 'a', 'a_2' ] ],
			[ [ "=a/b=\n=a.2Fb=", [ "a/b", "a.2Fb" ], "a.2Fb_2" ] ],
			[ [ "<h1 id='bar'>foo</h1>", 'bar' ] ],
			[ [ "<h1>foo</h1>\n=foo=\n<h1 id='foo'>bar</h1>", 'foo', 'foo_2', 'foo_3' ] ]
		];
	}

}

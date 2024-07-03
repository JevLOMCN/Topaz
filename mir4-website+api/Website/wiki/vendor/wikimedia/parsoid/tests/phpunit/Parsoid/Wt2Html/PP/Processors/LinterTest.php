<?php
declare( strict_types = 1 );

namespace Test\Parsoid\Wt2Html\PP\Processors;

use PHPUnit\Framework\TestCase;
use Wikimedia\Parsoid\Mocks\MockDataAccess;
use Wikimedia\Parsoid\Mocks\MockPageConfig;
use Wikimedia\Parsoid\Mocks\MockPageContent;
use Wikimedia\Parsoid\Mocks\MockSiteConfig;
use Wikimedia\Parsoid\Parsoid;

/**
 * Test cases for the linter
 *
 * @covers \Wikimedia\Parsoid\Wt2Html\PP\Processors\Linter
 */
class LinterTest extends TestCase {

	private function wtToLint( string $wt, array $linterOverrides = [] ): array {
		$siteOptions = [
			'linting' => true,
			'linterOverrides' => $linterOverrides,
		];
		$siteConfig = new MockSiteConfig( $siteOptions );

		$dataAccess = new MockDataAccess( $siteConfig, [] );
		$parsoid = new Parsoid( $siteConfig, $dataAccess );

		$content = new MockPageContent( [ 'main' => $wt ] );
		$pageConfig = new MockPageConfig( $siteConfig, [], $content );

		return $parsoid->wikitext2lint( $pageConfig, [] );
	}

	private function expectEmptyResults( string $description, string $wt, array $opts = [] ): void {
		$result = $this->wtToLint( $wt, $opts );
		$this->assertSame( [], $result, $description );
	}

	private function expectLinterCategoryToBeAbsent( string $description, string $wt, string $cat,
		array $opts = []
	): void {
		$result = $this->wtToLint( $wt, $opts );
		foreach ( $result as $r ) {
			if ( isset( $r['type'] ) ) {
				$this->assertNotEquals( $cat, $r['type'], $description );
			}
		}
	}

	private function noLintsOfThisType( string $description, string $wt, string $type ): void {
		$this->expectLinterCategoryToBeAbsent( $description, $wt, $type );
	}

	/**
	 * @covers \Wikimedia\Parsoid\Wt2Html\ParserPipeline
	 */
	public function testNoIssues(): void {
		$desc = 'should not have lint any issues';
		$this->expectEmptyResults( $desc, 'foo' );
	}

	/**
	 * @covers \Wikimedia\Parsoid\Wt2Html\ParserPipeline
	 */
	public function testMissingEndTags(): void {
		$desc = 'should lint missing end tags correctly';
		$result = $this->wtToLint( '<div>foo' );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'missing-end-tag', $result[0]['type'], $desc );
		$this->assertEquals( [ 0, 8, 5, 0 ], $result[0]['dsr'], $desc );
		$this->assertTrue( isset( $result[0]['params'] ), $desc );
		$this->assertEquals( 'div', $result[0]['params']['name'], $desc );

		$desc = 'should lint missing end tags for quotes correctly';
		$result = $this->wtToLint( "'''foo" );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'missing-end-tag', $result[0]['type'], $desc );
		$this->assertEquals( [ 0, 6, 3, 0 ], $result[0]['dsr'], $desc );
		$this->assertTrue( isset( $result[0]['params'] ), $desc );
		$this->assertEquals( 'b', $result[0]['params']['name'], $desc );

		$desc = 'should lint missing end tags found in transclusions correctly';
		$result = $this->wtToLint( '{{1x|<div>foo<p>bar</div>}}' );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'missing-end-tag', $result[0]['type'], $desc );
		$this->assertEquals( [ 0, 27, null, null ], $result[0]['dsr'], $desc );
		$this->assertTrue( isset( $result[0]['templateInfo'] ), $desc );
		$this->assertEquals( '1x', $result[0]['templateInfo']['name'], $desc );
		$this->assertTrue( isset( $result[0]['params'] ), $desc );
		$this->assertEquals( 'p', $result[0]['params']['name'], $desc );

		$desc = 'should not flag tags where end tags are optional in the spec';
		$wt = '<ul><li>x<li>y</ul><table><tr><th>heading 1<tr><td>col 1<td>col 2</table>';
		$this->expectEmptyResults( $desc, $wt );

		$desc = 'should lint missing end tag for wikitext table syntax';
		$result = $this->wtToLint( '{|\n|hiho' );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'missing-end-tag', $result[0]['type'], $desc );
		$this->assertEquals( [ 0, 9, 9, 0 ], $result[0]['dsr'], $desc );
	}

	/**
	 * @covers \Wikimedia\Parsoid\Wt2Html\ParserPipeline
	 */
	public function testStrippedTags(): void {
		$desc = 'should lint stripped tags correctly';
		$result = $this->wtToLint( 'foo</div>' );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'stripped-tag', $result[0]['type'], $desc );
		$this->assertTrue( isset( $result[0]['params'] ), $desc );
		$this->assertEquals( 'div', $result[0]['params']['name'], $desc );
		$this->assertEquals( [ 3, 9, null, null ], $result[0]['dsr'], $desc );

		$desc = 'should lint stripped tags found in transclusions correctly';
		$result = $this->wtToLint( '{{1x|<div>foo</div></div>}}' );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'stripped-tag', $result[0]['type'], $desc );
		$this->assertTrue( isset( $result[0]['params'] ), $desc );
		$this->assertEquals( 'div', $result[0]['params']['name'], $desc );
		$this->assertEquals( [ 0, 27, null, null ], $result[0]['dsr'], $desc );
		$this->assertTrue( isset( $result[0]['templateInfo'] ), $desc );
		$this->assertEquals( '1x', $result[0]['templateInfo']['name'], $desc );

		$desc = 'should lint stripped tags correctly in misnested tag situations (</i> is stripped)';
		$result = $this->wtToLint( '<b><i>X</b></i>' );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'misnested-tag', $result[0]['type'], $desc );
		$this->assertEquals( [ 3, 7, 3, 0 ], $result[0]['dsr'], $desc );
		$this->assertTrue( isset( $result[0]['params'] ), $desc );
		$this->assertEquals( 'i', $result[0]['params']['name'], $desc );

		$desc = 'should lint stripped tags correctly in misnested tag situations ' .
			'from template (</i> is stripped)';
		$result = $this->wtToLint( '{{1x|<b><i>X</b></i>}}' );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'misnested-tag', $result[0]['type'], $desc );
		$this->assertEquals( [ 0, 22, null, null ], $result[0]['dsr'], $desc );
		$this->assertTrue( isset( $result[0]['params'] ), $desc );
		$this->assertEquals( 'i', $result[0]['params']['name'], $desc );
		$this->assertTrue( isset( $result[0]['templateInfo'] ), $desc );
		$this->assertEquals( '1x', $result[0]['templateInfo']['name'], $desc );

		$desc = 'should lint stripped tags correctly in misnested tag situations (<i> is auto-inserted)';
		$result = $this->wtToLint( '<b><i>X</b>Y</i>' );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'misnested-tag', $result[0]['type'], $desc );
		$this->assertEquals( [ 3, 7, 3, 0 ], $result[0]['dsr'], $desc );
		$this->assertTrue( isset( $result[0]['params'] ), $desc );
		$this->assertEquals( 'i', $result[0]['params']['name'], $desc );

		$desc = 'should lint stripped tags correctly in misnested tag situations ' .
			'(skip over empty autoinserted <small></small>)';
		$result = $this->wtToLint( "*a<small>b\n*c</small>d" );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'misnested-tag', $result[0]['type'], $desc );
		$this->assertEquals( [ 2, 10, 7, 0 ], $result[0]['dsr'], $desc );
		$this->assertTrue( isset( $result[0]['params'] ), $desc );
		$this->assertEquals( 'small', $result[0]['params']['name'], $desc );

		$desc = 'should lint stripped tags correctly in misnested tag situations ' .
			'(formatting tags around lists, but ok for div)';
		$result = $this->wtToLint( "<small>a\n*b\n*c\nd</small>\n<div>a\n*b\n*c\nd</div>" );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'misnested-tag', $result[0]['type'], $desc );
		$this->assertEquals( [ 0, 8, 7, 0 ], $result[0]['dsr'], $desc );
		$this->assertTrue( isset( $result[0]['params'] ), $desc );
		$this->assertEquals( 'small', $result[0]['params']['name'], $desc );

		$desc = 'should lint stripped tags correctly in misnested tag situations ' .
			'(T221989 regression test case)';
		$result = $this->wtToLint( "<div>\n* <span>foo\n\n</div>\n</span>y" );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'misnested-tag', $result[0]['type'], $desc );
		$this->assertEquals( [ 8, 17, 6, 0 ], $result[0]['dsr'], $desc );
		$this->assertTrue( isset( $result[0]['params'] ), $desc );
		$this->assertEquals( 'span', $result[0]['params']['name'], $desc );

		$desc = 'should lint stripped tags in nested pipelines (T338325)';
		$result = $this->wtToLint(
			"[[Special:Contributions/Yellow Evan|Hurricane</font>]]\n\n" .
			"[[File:Foobar.jpg|thumb|[[Normande Cattle|Normande cow]]</center>]]\n\n" .
			"<gallery>\n" .
			"File:Foobar.jpg|... a [[black-collared barbet]], waiting to be fed</center>\n" .
			"</gallery>"
		);
		$this->assertCount( 3, $result, $desc );
		$this->assertEquals( 'stripped-tag', $result[0]['type'], $desc );
		$this->assertEquals( 'stripped-tag', $result[1]['type'], $desc );
		$this->assertEquals( 'stripped-tag', $result[2]['type'], $desc );
	}

	/**
	 * @covers \Wikimedia\Parsoid\Wt2Html\ParserPipeline
	 */
	public function testObsoleteTags(): void {
		$desc = 'should lint obsolete tags correctly';
		$result = $this->wtToLint( '<tt>foo</tt>bar' );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'obsolete-tag', $result[0]['type'], $desc );
		$this->assertEquals( [ 0, 12, 4, 5 ], $result[0]['dsr'], $desc );
		$this->assertTrue( isset( $result[0]['params'] ), $desc );
		$this->assertEquals( 'tt', $result[0]['params']['name'], $desc );

		$desc = 'should not lint big as an obsolete tag';
		$this->expectEmptyResults( $desc, '<big>foo</big>bar' );

		$desc = 'should lint obsolete tags found in transclusions correctly';
		$result = $this->wtToLint( '{{1x|<div><tt>foo</tt></div>}}foo' );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'obsolete-tag', $result[0]['type'], $desc );
		$this->assertEquals( [ 0, 30, null, null ], $result[0]['dsr'], $desc );
		$this->assertTrue( isset( $result[0]['params'] ), $desc );
		$this->assertEquals( 'tt', $result[0]['params']['name'], $desc );
		$this->assertTrue( isset( $result[0]['templateInfo'] ), $desc );
		$this->assertEquals( '1x', $result[0]['templateInfo']['name'], $desc );

		$desc = 'should not lint auto-inserted obsolete tags';
		$result = $this->wtToLint( "<tt>foo\n\n\nbar" );
		// obsolete-tag and missing-end-tag
		$this->assertCount( 2, $result, $desc );
		$this->assertEquals( 'missing-end-tag', $result[0]['type'], $desc );
		$this->assertEquals( 'obsolete-tag', $result[1]['type'], $desc );
		$this->assertEquals( [ 0, 7, 4, 0 ], $result[1]['dsr'], $desc );
		$this->assertTrue( isset( $result[1]['params'] ), $desc );
		$this->assertEquals( 'tt', $result[1]['params']['name'], $desc );

		$desc = 'should not have template info for extension tags';
		$result = $this->wtToLint( "<gallery>\nFile:Test.jpg|<tt>foo</tt>\n</gallery>" );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'obsolete-tag', $result[0]['type'], $desc );
		$this->assertFalse( isset( $result[0]['templateInfo'] ), $desc );
		$this->assertEquals( [ 24, 36, 4, 5 ], $result[0]['dsr'], $desc );
	}

	/**
	 * @covers \Wikimedia\Parsoid\Wt2Html\ParserPipeline
	 */
	public function testFosteredContent(): void {
		$desc = 'should lint fostered content correctly';
		$result = $this->wtToLint( "{|\nfoo\n|-\n| bar\n|}" );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'fostered', $result[0]['type'], $desc );
		$this->assertEquals( [ 0, 18, 2, 2 ], $result[0]['dsr'], $desc );

		$desc = 'should not lint fostered categories';
		$this->expectEmptyResults( $desc, "{|\n[[Category:Fostered]]\n|-\n| bar\n|}" );

		$desc = 'should not lint fostered behavior switches';
		$this->expectEmptyResults( $desc, "{|\n__NOTOC__\n|-\n| bar\n|}" );

		$desc = 'should not lint fostered include directives without fostered content';
		$this->expectEmptyResults( $desc, "{|\n<includeonly>boo</includeonly>\n|-\n| bar\n|}" );

		$desc = 'should lint fostered include directives that has fostered content';
		$result = $this->wtToLint( "{|\n<noinclude>boo</noinclude>\n|-\n| bar\n|}" );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'fostered', $result[0]['type'], $desc );

		$desc = 'should not lint fostered section tags';
		$this->expectEmptyResults( $desc, "{|\n<section name='123'/>\n|-\n| bar\n|}" );
	}

	/**
	 * @covers \Wikimedia\Parsoid\Wt2Html\ParserPipeline
	 */
	public function testBogusImageOptions(): void {
		$desc = 'should lint Bogus image options correctly';
		$result = $this->wtToLint( '[[file:a.jpg|foo|bar]]' );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'bogus-image-options', $result[0]['type'], $desc );
		$this->assertEquals( [ 0, 22, null, null ], $result[0]['dsr'], $desc );
		$this->assertTrue( isset( $result[0]['params'] ), $desc );
		$this->assertTrue( isset( $result[0]['params']['items'] ), $desc );
		$this->assertEquals( 'foo', $result[0]['params']['items'][0], $desc );

		$desc = 'should lint Bogus image options found in transclusions correctly';
		$result = $this->wtToLint( '{{1x|[[file:a.jpg|foo|bar]]}}' );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'bogus-image-options', $result[0]['type'], $desc );
		$this->assertEquals( [ 0, 29, null, null ], $result[0]['dsr'], $desc );
		$this->assertTrue( isset( $result[0]['params'] ), $desc );
		$this->assertEquals( 'foo', $result[0]['params']['items'][0], $desc );
		$this->assertTrue( isset( $result[0]['templateInfo'] ), $desc );
		$this->assertEquals( '1x', $result[0]['templateInfo']['name'], $desc );

		$desc = 'should batch lint Bogus image options correctly';
		$result = $this->wtToLint( '[[file:a.jpg|foo|bar|baz]]' );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'bogus-image-options', $result[0]['type'], $desc );
		$this->assertEquals( [ 0, 26, null, null ], $result[0]['dsr'], $desc );
		$this->assertTrue( isset( $result[0]['params'] ), $desc );
		$this->assertEquals( 'foo', $result[0]['params']['items'][0], $desc );
		$this->assertEquals( 'bar', $result[0]['params']['items'][1], $desc );

		$desc = 'should not send any Bogus image options if there are none';
		$this->expectEmptyResults( $desc, '[[file:a.jpg|foo]]' );

		$desc = 'should flag disablecontrols as bogus options';
		$result = $this->wtToLint(
		'[[File:Video.ogv|disablecontrols=ok|These are bogus.]]' );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'bogus-image-options', $result[0]['type'], $desc );
		$this->assertEquals( [ 0, 54, null, null ], $result[0]['dsr'], $desc );
		$this->assertTrue( isset( $result[0]['params'] ), $desc );
		$this->assertEquals( 'disablecontrols=ok', $result[0]['params']['items'][0], $desc );

		$desc = 'should not crash on gallery images';
		$this->expectEmptyResults( $desc, "<gallery>\nfile:a.jpg\n</gallery>" );

		$desc = 'should lint Bogus image width options correctly';
		$result = $this->wtToLint( '[[File:Foobar.jpg|thumb|left150px|Caption]]' );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'bogus-image-options', $result[0]['type'], $desc );
		$this->assertEquals( [ 0, 43, 2, 2 ], $result[0]['dsr'], $desc );

		$desc = "should lint Bogus image with bogus width definition correctly";
		$result = $this->wtToLint(
			"[[File:Foobar.jpg|thumb|300px300px|Caption]]" );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'bogus-image-options', $result[0]['type'], $desc );
		$this->assertEquals( [ 0, 44, 2, 2 ], $result[0]['dsr'], $desc );

		$desc = "should lint Bogus image with duplicate width options correctly";
		$result = $this->wtToLint(
			"[[File:Foobar.jpg|thumb|300px|250px|Caption]]" );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'bogus-image-options', $result[0]['type'], $desc );
		$this->assertEquals( [ 0, 45, 2, 2 ], $result[0]['dsr'], $desc );
		$this->assertTrue( isset( $result[0]['params'] ), $desc );
		$this->assertEquals( '300px', $result[0]['params']['items'][0], $desc );

		$desc = "should lint Bogus image with separated duplicate widths options correctly";
		$result = $this->wtToLint(
			"[[File:Foobar.jpg|thumb|250px|right|thumb|x216px|Caption]]" );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'bogus-image-options', $result[0]['type'], $desc );
		$this->assertEquals( [ 0, 58, 2, 2 ], $result[0]['dsr'], $desc );
		$this->assertTrue( isset( $result[0]['params'] ), $desc );
		$this->assertEquals( '250px', $result[0]['params']['items'][0], $desc );
		// The second '|thumb' should be linted away
		$this->assertEquals( 'thumb', $result[0]['params']['items'][1], $desc );

		$desc = 'should not lint image with caption masquerading as width option';
		$result = $this->wtToLint( '[[File:Foobar.jpg|thumb|Foo px]]' );
		$this->assertCount( 0, $result, $desc );

		$desc = "should lint image with width option with redundant units";
		$result = $this->wtToLint(
			"[[File:Foobar.jpg|thumb|250pxpx|right|Caption]]" );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'bogus-image-options', $result[0]['type'], $desc );
		$this->assertEquals( [ 0, 47, 2, 2 ], $result[0]['dsr'], $desc );
		$this->assertTrue( isset( $result[0]['params'] ), $desc );
		$this->assertEquals( '250pxpx', $result[0]['params']['items'][0], $desc );

		$desc = "should lint Bogus image with invalid upright value";
		$result = $this->wtToLint(
			'[[File:Foobar.jpg|thumb|upright=0.7px|Caption]]' .
			'[[File:Foobar.jpg|thumb|upright=1.5"|Caption]]' .
			'[[File:Foobar.jpg|thumb|upright=0|Caption]]' .
			'[[File:Foobar.jpg|thumb|upright=-1|Caption]]' .
			'[[File:Foobar.jpg|thumb|upright=1.5|Caption]]'
		);
		$this->assertCount( 4, $result, $desc );
		$this->assertEquals( 'bogus-image-options', $result[0]['type'], $desc );
		$this->assertEquals( [ 0, 47, 2, 2 ], $result[0]['dsr'], $desc );
		$this->assertTrue( isset( $result[0]['params'] ), $desc );
		$this->assertEquals( 'upright=0.7px', $result[0]['params']['items'][0], $desc );
		$this->assertEquals( 'bogus-image-options', $result[1]['type'], $desc );
		$this->assertEquals( [ 47, 93, 2, 2 ], $result[1]['dsr'], $desc );
		$this->assertTrue( isset( $result[1]['params'] ), $desc );
		$this->assertEquals( 'upright=1.5"', $result[1]['params']['items'][0], $desc );
		$this->assertEquals( 'bogus-image-options', $result[2]['type'], $desc );
		$this->assertEquals( [ 93, 136, 2, 2 ], $result[2]['dsr'], $desc );
		$this->assertTrue( isset( $result[2]['params'] ), $desc );
		$this->assertEquals( 'upright=0', $result[2]['params']['items'][0], $desc );
		$this->assertEquals( 'bogus-image-options', $result[3]['type'], $desc );
		$this->assertEquals( [ 136, 180, 2, 2 ], $result[3]['dsr'], $desc );
		$this->assertTrue( isset( $result[3]['params'] ), $desc );
		$this->assertEquals( 'upright=-1', $result[3]['params']['items'][0], $desc );

		$desc = "should lint multiple media formats, first one wins";
		$result = $this->wtToLint(
			'[[File:Foobar.jpg|frame|frameless]]' .
			'[[File:Foobar.jpg|frameless|frame]]' .
			'[[File:Foobar.jpg|thumbnail=Thumb.png|thumb]]'
		);
		$this->assertCount( 3, $result, $desc );
		$this->assertEquals( 'bogus-image-options', $result[0]['type'], $desc );
		$this->assertEquals( [ 0, 35, 2, 2 ], $result[0]['dsr'], $desc );
		$this->assertTrue( isset( $result[0]['params'] ), $desc );
		$this->assertEquals( 'frameless', $result[0]['params']['items'][0], $desc );
		$this->assertEquals( 'bogus-image-options', $result[1]['type'], $desc );
		$this->assertEquals( [ 35, 70, null, null ], $result[1]['dsr'], $desc );
		$this->assertTrue( isset( $result[1]['params'] ), $desc );
		$this->assertEquals( 'frame', $result[1]['params']['items'][0], $desc );
		$this->assertEquals( 'bogus-image-options', $result[2]['type'], $desc );
		$this->assertEquals( [ 70, 115, 2, 2 ], $result[2]['dsr'], $desc );
		$this->assertTrue( isset( $result[2]['params'] ), $desc );
		$this->assertEquals( 'thumb', $result[2]['params']['items'][0], $desc );

		$desc = "should lint defined with for framed or manualthumb formats";
		$result = $this->wtToLint(
			'[[File:Foobar.jpg|frame|200px]]' .
			'[[File:Foobar.jpg|thumbnail=Thumb.png|400px]]'
		);
		$this->assertCount( 2, $result, $desc );
		$this->assertEquals( 'bogus-image-options', $result[0]['type'], $desc );
		$this->assertEquals( [ 0, 31, 2, 2 ], $result[0]['dsr'], $desc );
		$this->assertTrue( isset( $result[0]['params'] ), $desc );
		$this->assertEquals( '200px', $result[0]['params']['items'][0], $desc );
		$this->assertEquals( 'bogus-image-options', $result[1]['type'], $desc );
		$this->assertEquals( [ 31, 76, 2, 2 ], $result[1]['dsr'], $desc );
		$this->assertTrue( isset( $result[1]['params'] ), $desc );
		$this->assertEquals( '400px', $result[1]['params']['items'][0], $desc );

		$desc = "should lint format option used in gallery";
		$result = $this->wtToLint(
			'<gallery>\n' .
			'File:Foobar.jpg|thumb|lalala\n' .
			'</gallery>\n' .
			'{{1x|<gallery>\n' .
			'File:Foobar.jpg|frame|hihi\n' .
			'</gallery>}}\n'
		);
		$this->assertCount( 2, $result, $desc );

		$this->assertEquals( 'bogus-image-options', $result[0]['type'], $desc );
		$this->assertEquals( [ 0, 51, 9, 10 ], $result[0]['dsr'], $desc );
		$this->assertTrue( isset( $result[0]['params'] ), $desc );
		$this->assertEquals( 'thumb', $result[0]['params']['items'][0], $desc );

		$this->assertEquals( 'bogus-image-options', $result[1]['type'], $desc );
		$this->assertEquals( [ 53, 109, null, null ], $result[1]['dsr'], $desc );
		$this->assertTrue( isset( $result[1]['params'] ), $desc );
		$this->assertEquals( 'frame', $result[1]['params']['items'][0], $desc );
		$this->assertTrue( isset( $result[1]['templateInfo'] ), $desc );
		$this->assertEquals( '1x', $result[1]['templateInfo']['name'], $desc );

		$desc = "should lint size option used in gallery";
		$result = $this->wtToLint(
			'<gallery>\n' .
			'File:Foobar.jpg|500px|lalala\n' .
			'</gallery>'
		);
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'bogus-image-options', $result[0]['type'], $desc );
		$this->assertEquals( [ 0, 51, 9, 10 ], $result[0]['dsr'], $desc );
		$this->assertTrue( isset( $result[0]['params'] ), $desc );
		$this->assertEquals( '500px', $result[0]['params']['items'][0], $desc );
	}

	/**
	 * @covers \Wikimedia\Parsoid\Wt2Html\ParserPipeline
	 */
	public function testSelfClosingTags(): void {
		$desc = 'should lint self-closing tags corrrectly';
		$result = $this->wtToLint( "foo<b />bar<span />baz<hr />boo<br /> <ref name='boo' />" );
		$this->assertCount( 2, $result, $desc );
		$this->assertEquals( 'self-closed-tag', $result[0]['type'], $desc );
		$this->assertEquals( [ 3, 8, 5, 0 ], $result[0]['dsr'], $desc );
		$this->assertTrue( isset( $result[0]['params'] ), $desc );
		$this->assertEquals( 'b', $result[0]['params']['name'], $desc );
		$this->assertEquals( 'self-closed-tag', $result[1]['type'], $desc );
		$this->assertEquals( [ 11, 19, 8, 0 ], $result[1]['dsr'], $desc );
		$this->assertTrue( isset( $result[1]['params'] ), $desc );
		$this->assertEquals( 'span', $result[1]['params']['name'], $desc );

		$desc = 'should lint self-closing tags in a template correctly';
		$result = $this->wtToLint( "{{1x|<b /> <ref name='boo' />}}" );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'self-closed-tag', $result[0]['type'], $desc );
		$this->assertEquals( [ 0, 31, null, null ], $result[0]['dsr'], $desc );
		$this->assertTrue( isset( $result[0]['params'] ), $desc );
		$this->assertEquals( 'b', $result[0]['params']['name'][0], $desc );
		$this->assertTrue( isset( $result[0]['templateInfo'] ), $desc );
		$this->assertEquals( '1x', $result[0]['templateInfo']['name'], $desc );
	}

	/**
	 * @covers \Wikimedia\Parsoid\Wt2Html\ParserPipeline
	 */
	public function testDeletableTableTag(): void {
		$desc = 'should identify deletable table tag for T161341 (1)';
		$wt = implode( "\n", [
			"{| style='border:1px solid red;'",
			"|a",
			"|-",
			"{| style='border:1px solid blue;'",
			"|b",
			"|c",
			"|}",
			"|}"
		] );
		$result = $this->wtToLint( $wt );
		$this->assertCount( 2, $result, $desc );
		$this->assertEquals( 'deletable-table-tag', $result[0]['type'], $desc );
		$this->assertTrue( isset( $result[0]['params'] ), $desc );
		$this->assertEquals( 'table', $result[0]['params']['name'], $desc );
		$this->assertEquals( [ 39, 72, 0, 0 ], $result[0]['dsr'], $desc );

		$desc = 'should identify deletable table tag for T161341 (2)';
		$wt = implode( "\n", [
			"{| style='border:1px solid red;'",
			"|a",
			"|-  ",
			"   <!--boo-->   ",
			"{| style='border:1px solid blue;'",
			"|b",
			"|c",
			"|}"
		] );
		$result = $this->wtToLint( $wt );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'deletable-table-tag', $result[0]['type'], $desc );
		$this->assertTrue( isset( $result[0]['params'] ), $desc );
		$this->assertEquals( 'table', $result[0]['params']['name'], $desc );
		$this->assertEquals( [ 58, 91, 0, 0 ], $result[0]['dsr'], $desc );

		$desc = 'should identify deletable table tag for T161341 (3)';
		$wt = implode( "\n", [
			"{{1x|{{{!}}",
			"{{!}}a",
			"{{!}}-",
			"{{{!}}",
			"{{!}}b",
			"{{!}}c",
			"{{!}}}",
			"}}"
		] );
		$result = $this->wtToLint( $wt );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'deletable-table-tag', $result[0]['type'], $desc );
		$this->assertTrue( isset( $result[0]['templateInfo'] ), $desc );
		$this->assertEquals( '1x', $result[0]['templateInfo']['name'], $desc );
		$this->assertEquals( [ 0, 56, null, null ], $result[0]['dsr'], $desc );

		$desc = 'should identify deletable table tag for T161341 (4)';
		$wt = implode( "\n", [
			"{{1x|{{{!}}",
			"{{!}}a",
			"{{!}}-",
			"}}",
			"{|",
			"|b",
			"|c",
			"|}"
		] );
		$result = $this->wtToLint( $wt );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'deletable-table-tag', $result[0]['type'], $desc );
		$this->assertFalse( isset( $result[0]['templateInfo'] ), $desc );
		$this->assertTrue( isset( $result[0]['params'] ), $desc );
		$this->assertEquals( 'table', $result[0]['params']['name'], $desc );
		$this->assertEquals( [ 29, 31, 0, 0 ], $result[0]['dsr'], $desc );
	}

	/**
	 * @covers \Wikimedia\Parsoid\Wt2Html\ParserPipeline
	 */
	public function testPwrapBugWorkaround(): void {
		$desc = 'should identify rendering workarounds needed for doBlockLevels bug';
		$wt = implode( "\n", [
			"<div><span style='white-space:nowrap'>",
			"a",
			"</span>",
			"</div>"
		] );
		$result = $this->wtToLint( $wt );
		$this->assertCount( 3, $result, $desc );
		$this->assertEquals( 'pwrap-bug-workaround', $result[1]['type'], $desc );
		$this->assertFalse( isset( $result[1]['templateInfo'] ), $desc );
		$this->assertTrue( isset( $result[1]['params'] ), $desc );
		$this->assertEquals( 'div', $result[1]['params']['root'], $desc );
		$this->assertEquals( 'span', $result[1]['params']['child'], $desc );
		$this->assertEquals( [ 5, 48, 33, 0 ], $result[1]['dsr'], $desc );

		$desc = 'should not lint doBlockLevels bug rendering workarounds if newline break is present';
		$wt = implode( "\n", [
			"<div>",
			"<span style='white-space:nowrap'>",
			"a",
			"</span>",
			"</div>"
		] );
		$this->expectLinterCategoryToBeAbsent( $desc, $wt, 'pwrap-bug-workaround' );

		$desc = 'should not lint doBlockLevels bug rendering workarounds if nowrap CSS is not present';
		$wt = implode( "\n", [
			"<div><span>",
			"a",
			"</span>",
			"</div>"
		] );
		 $this->expectLinterCategoryToBeAbsent( $desc, $wt, 'pwrap-bug-workaround' );

		$desc = 'should not lint doBlockLevels bug rendering workarounds where not required';
		$wt = implode( "\n", [
			"<div><small style='white-space:nowrap'>",
			"a",
			"</small>",
			"</div>"
		] );
		$this->expectLinterCategoryToBeAbsent( $desc, $wt, 'pwrap-bug-workaround' );
	}

	/**
	 * @covers \Wikimedia\Parsoid\Wt2Html\ParserPipeline
	 */
	public function testTidyWhitespaceBug(): void {
		$wt1 = implode( "", [
			// Basic with inline CSS + text sibling
			"<span style='white-space:nowrap'>a </span>",
			"x",
			// Basic with inline CSS + span sibling
			"<span style='white-space:nowrap'>a </span>",
			"<span>x</span>",
			// Basic with class CSS + text sibling
			"<span class='nowrap'>a </span>",
			"x",
			// Basic with class CSS + span sibling
			"<span class='nowrap'>a </span>",
			"<span>x</span>",
			// Comments shouldn't trip it up
			"<span style='white-space:nowrap'>a<!--boo--> <!--boo--></span>",
			"<!--boo-->",
			"<span>x</span>"
		] );

		$desc = 'should detect problematic whitespace hoisting';
		$result = $this->wtToLint( $wt1, [ 'tidyWhitespaceBugMaxLength' => 0 ] );
		$this->assertCount( 5, $result, $desc );
		foreach ( $result as $r ) {
			$this->assertEquals( 'tidy-whitespace-bug', $r['type'], $desc );
			$this->assertEquals( 'span', $r['params']['node'], $desc );
		}
		$this->assertEquals( '#text', $result[0]['params']['sibling'], $desc );
		$this->assertEquals( 'span', $result[1]['params']['sibling'], $desc );
		$this->assertEquals( '#text', $result[2]['params']['sibling'], $desc );
		$this->assertEquals( 'span', $result[3]['params']['sibling'], $desc );
		$this->assertEquals( '#comment', $result[4]['params']['sibling'], $desc );
		// skipping dsr tests

		$desc = 'should not detect problematic whitespace hoisting for short text runs';
		// Nothing to trigger here
		$this->expectEmptyResults( $desc, $wt1, [ 'tidyWhitespaceBugMaxLength' => 100 ] );

		$wt2 = implode( "", [
			"some unaffected text here ",
			"<span style='white-space:nowrap'>a </span>",
			"<span style='white-space:nowrap'>bb</span>",
			"<span class='nowrap'>cc</span>",
			"<span class='nowrap'>d </span>",
			"<span style='white-space:nowrap'>e </span>",
			"<span class='nowrap'>x</span>"
		] );

		$desc = 'should flag tidy whitespace bug on a run of affected content';
		// The run length is 11 chars in the example above
		$result = $this->wtToLint( $wt2, [ 'tidyWhitespaceBugMaxLength' => 5 ] );
		$this->assertCount( 3, $result, $desc );
		foreach ( $result as $r ) {
			$this->assertEquals( 'tidy-whitespace-bug', $r['type'], $desc );
			$this->assertEquals( 'span', $r['params']['node'], $desc );
		}
		$this->assertEquals( 'span', $result[0]['params']['sibling'], $desc );
		$this->assertEquals( [ 26, 68, 33, 7 ], $result[0]['dsr'], $desc );
		$this->assertEquals( 'span', $result[1]['params']['sibling'], $desc );
		$this->assertEquals( [ 140, 170, 21, 7 ], $result[1]['dsr'], $desc );
		$this->assertEquals( 'span', $result[2]['params']['sibling'], $desc );
		$this->assertEquals( [ 170, 212, 33, 7 ], $result[2]['dsr'], $desc );

		$desc = 'should not flag tidy whitespace bug on a run of short affected content';
		$this->expectEmptyResults( $desc, $wt2, [ 'tidyWhitespaceBugMaxLength' => 12 ] );

		$desc = 'should account for preceding text content';
		// Run length changes to 16 chars because of preceding text
		$wt2 = str_replace( 'some unaffected text here ', 'some unaffected text HERE-', $wt2 );
		$result = $this->wtToLint( $wt2, [ 'tidyWhitespaceBugMaxLength' => 12 ] );
		$this->assertCount( 3, $result, $desc );
		foreach ( $result as $r ) {
			$this->assertEquals( 'tidy-whitespace-bug', $r['type'], $desc );
			$this->assertEquals( 'span', $r['params']['node'], $desc );
		}
		$this->assertEquals( 'span', $result[0]['params']['sibling'], $desc );
		$this->assertEquals( [ 26, 68, 33, 7 ], $result[0]['dsr'], $desc );
		$this->assertEquals( 'span', $result[1]['params']['sibling'], $desc );
		$this->assertEquals( [ 140, 170, 21, 7 ], $result[1]['dsr'], $desc );
		$this->assertEquals( 'span', $result[2]['params']['sibling'], $desc );
		$this->assertEquals( [ 170, 212, 33, 7 ], $result[2]['dsr'], $desc );

		$desc = 'should not flag tidy whitespace bug where it does not matter';
		$wt = implode( "", [
			// No CSS
			"<span>a </span>",
			"<span>x</span>",
			// No trailing white-space
			"<span class='nowrap'>a</span>",
			"x",
			// White-space follows
			"<span class='nowrap'>a </span>",
			" ",
			"<span>x</span>",
			// White-space follows
			"<span style='white-space:nowrap'>a </span>",
			"<!--boo--> boo",
			"<span>x</span>",
			// Block tag
			"<div class='nowrap'>a </div>",
			"<span>x</span>",
			// Block tag sibling
			"<span class='nowrap'>a </span>",
			"<div>x</div>",
			// br sibling
			"<span class='nowrap'>a </span>",
			"<br/>",
			// No next sibling
			"<span class='nowrap'>a </span>"
		] );
		$this->expectEmptyResults( $desc, $wt, [ 'tidyWhitespaceBugMaxLength' => 0 ] );
	}

	/**
	 * @covers \Wikimedia\Parsoid\Wt2Html\ParserPipeline
	 */
	public function testMultipleColonEscape(): void {
		$desc = 'should lint links prefixed with multiple colons';
		$result = $this->wtToLint( "[[None]]\n[[:One]]\n[[::Two]]\n[[:::Three]]" );
		$this->assertCount( 2, $result, $desc );
		$this->assertEquals( 'multi-colon-escape', $result[0]['type'], $desc );
		$this->assertEquals( [ 18, 27, null, null ], $result[0]['dsr'], $desc );
		$this->assertTrue( isset( $result[0]['params'] ), $desc );
		$this->assertEquals( '::Two', $result[0]['params']['href'], $desc );
		$this->assertEquals( 'multi-colon-escape', $result[1]['type'], $desc );
		$this->assertEquals( [ 28, 40, null, null ], $result[1]['dsr'], $desc );
		$this->assertTrue( isset( $result[1]['params'] ), $desc );
		$this->assertEquals( ':::Three', $result[1]['params']['href'], $desc );

		$desc = 'should lint links prefixed with multiple colons from templates';
		$result = $this->wtToLint( "{{1x|[[:One]]}}\n{{1x|[[::Two]]}}" );
		$this->assertCount( 1, $result, $desc );
		$this->assertTrue( isset( $result[0]['templateInfo'] ), $desc );
		$this->assertEquals( '1x', $result[0]['templateInfo']['name'], $desc );
		// TODO(arlolra): Frame doesn't have tsr info yet
		$this->assertEquals( [ 0, 0, null, null ], $result[0]['dsr'], $desc );
		$this->assertTrue( isset( $result[0]['params'] ), $desc );
		$this->assertEquals( '::Two', $result[0]['params']['href'], $desc );
	}

	/**
	 * @covers \Wikimedia\Parsoid\Wt2Html\ParserPipeline
	 */
	public function testHtml5MisnestedTags(): void {
		$desc = "should not trigger html5 misnesting if there is no following content";
		$result = $this->wtToLint( "<del>foo\nbar" );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'missing-end-tag', $result[0]['type'], $desc );
		$this->assertTrue( isset( $result[0]['params'] ), $desc );
		$this->assertEquals( 'del', $result[0]['params']['name'], $desc );

		$desc = "should trigger html5 misnesting correctly";
		$result = $this->wtToLint( "<del>foo\n\nbar" );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'html5-misnesting', $result[0]['type'], $desc );
		$this->assertEquals( [ 0, 8, 5, 0 ], $result[0]['dsr'], $desc );
		$this->assertTrue( isset( $result[0]['params'] ), $desc );
		$this->assertEquals( 'del', $result[0]['params']['name'], $desc );

		$desc = "should trigger html5 misnesting for span (1)";
		$result = $this->wtToLint( "<span>foo\n\nbar" );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'html5-misnesting', $result[0]['type'], $desc );
		$this->assertEquals( [ 0, 9, 6, 0 ], $result[0]['dsr'], $desc );
		$this->assertTrue( isset( $result[0]['params'] ), $desc );
		$this->assertEquals( 'span', $result[0]['params']['name'], $desc );

		$desc = "should trigger html5 misnesting for span (2)";
		$result = $this->wtToLint( "<span>foo\n\n<div>bar</div>" );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'html5-misnesting', $result[0]['type'], $desc );
		$this->assertEquals( [ 0, 9, 6, 0 ], $result[0]['dsr'], $desc );
		$this->assertTrue( isset( $result[0]['params'] ), $desc );
		$this->assertEquals( 'span', $result[0]['params']['name'], $desc );

		$desc = "should trigger html5 misnesting for span (3)";
		$result = $this->wtToLint( "<span>foo\n\n{|\n|x\n|}\nboo" );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'html5-misnesting', $result[0]['type'], $desc );
		$this->assertEquals( [ 0, 9, 6, 0 ], $result[0]['dsr'], $desc );
		$this->assertTrue( isset( $result[0]['params'] ), $desc );
		$this->assertEquals( 'span', $result[0]['params']['name'], $desc );

		$desc = "should not trigger html5 misnesting when there is no misnested content";
		$result = $this->wtToLint( "<span>foo\n\n</span>y" );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'misnested-tag', $result[0]['type'], $desc );
		$this->assertTrue( isset( $result[0]['params'] ), $desc );
		$this->assertEquals( 'span', $result[0]['params']['name'], $desc );

		$desc = "should not trigger html5 misnesting when unclosed tag is inside a td/th/heading tags";
		$result = $this->wtToLint( "=<span id=\"1\">x=\n{|\n!<span id=\"2\">z\n|-\n|<span>id=\"3\"\n|}" );
		$this->assertCount( 3, $result, $desc );
		$this->assertEquals( 'missing-end-tag-in-heading', $result[0]['type'], $desc );
		$this->assertEquals( 'missing-end-tag', $result[1]['type'], $desc );
		$this->assertEquals( 'missing-end-tag', $result[2]['type'], $desc );

		$desc = "should not trigger html5 misnesting when misnested content is " .
			"outside an a-tag (without link-trails)";
		$result = $this->wtToLint( "[[Foo|<span>foo]]Bar</span>" );
		$this->assertCount( 2, $result, $desc );
		$this->assertEquals( 'missing-end-tag', $result[0]['type'], $desc );
		$this->assertTrue( isset( $result[0]['params'] ), $desc );
		$this->assertEquals( 'span', $result[0]['params']['name'], $desc );
		$this->assertEquals( 'stripped-tag', $result[1]['type'], $desc );

		// Note that this is a false positive because of T177086 and fixing that will fix this.
		// We expect this to be an edge case.
		$desc = "should trigger html5 misnesting when linktrails brings content inside an a-tag";
		$result = $this->wtToLint( "[[Foo|<span>foo]]bar</span>" );
		$this->assertCount( 2, $result, $desc );
		$this->assertEquals( 'html5-misnesting', $result[0]['type'], $desc );
		$this->assertTrue( isset( $result[0]['params'] ), $desc );
		$this->assertEquals( 'span', $result[0]['params']['name'], $desc );
		$this->assertEquals( 'stripped-tag', $result[1]['type'], $desc );

		$desc = "should not trigger html5 misnesting for formatting tags";
		$result = $this->wtToLint( "<small>foo\n\nbar" );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'missing-end-tag', $result[0]['type'], $desc );
		$this->assertTrue( isset( $result[0]['params'] ), $desc );
		$this->assertEquals( 'small', $result[0]['params']['name'], $desc );

		$desc = "should not trigger html5 misnesting for span if there is a nested span tag";
		$result = $this->wtToLint( "<span>foo<span>boo</span>\n\nbar</span>" );
		$this->assertCount( 2, $result, $desc );
		$this->assertEquals( 'missing-end-tag', $result[0]['type'], $desc );
		$this->assertEquals( 'stripped-tag', $result[1]['type'], $desc );

		$desc = "should trigger html5 misnesting for span if there is a nested non-span tag";
		$result = $this->wtToLint( "<span>foo<del>boo</del>\n\nbar</span>" );
		$this->assertCount( 2, $result, $desc );
		$this->assertEquals( 'html5-misnesting', $result[0]['type'], $desc );
		$this->assertTrue( isset( $result[0]['params'] ), $desc );
		$this->assertEquals( 'span', $result[0]['params']['name'], $desc );
		$this->assertEquals( 'stripped-tag', $result[1]['type'], $desc );

		$desc = "should trigger html5 misnesting for span if there is a nested unclosed span tag";
		$result = $this->wtToLint( "<span>foo<span>boo\n\nbar</span>" );
		$this->assertCount( 3, $result, $desc );
		$this->assertEquals( 'html5-misnesting', $result[0]['type'], $desc );
		$this->assertEquals( 'html5-misnesting', $result[1]['type'], $desc );
		$this->assertEquals( 'stripped-tag', $result[2]['type'], $desc );
	}

	/**
	 * @covers \Wikimedia\Parsoid\Wt2Html\ParserPipeline
	 */
	public function testTidyFontBug(): void {
		$desc = "should flag Tidy font fixups accurately when color attribute is present";
		$wtLines = [
			"<font color='green'>[[Foo]]</font>",
			"<font color='green'>[[Category:Boo]][[Foo]]</font>",
			"<font color='green'>__NOTOC__[[Foo]]</font>",
			"<font color='green'><!--boo-->[[Foo]]</font>",
			"<font color='green'>[[Foo|bar]]</font>",
			"<font color='green'>[[Foo|''bar'']]</font>",
			"<font color='green'>[[Foo|''bar'' and boo]]</font>",
			"<font color='green'>[[Foo]]l</font>",
			"<font color='green'>{{1x|[[Foo]]}}</font>"
		];
		$n = count( $wtLines );
		$result = $this->wtToLint( implode( "\n", $wtLines ) );
		$this->assertCount( 2 * $n, $result, $desc );
		for ( $i = 0; $i < 2 * $n; $i += 2 ) {
			$this->assertEquals( 'obsolete-tag', $result[$i]['type'], $desc );
			$this->assertEquals( 'tidy-font-bug', $result[$i + 1]['type'], $desc );
		}

		$desc = "should not flag Tidy font fixups when color attribute is absent";
		$wtLinesReplaced = str_replace( " color='green'", '', $wtLines );
		$n = count( $wtLinesReplaced );
		$result = $this->wtToLint( implode( "\n", $wtLinesReplaced ) );
		$this->assertCount( $n, $wtLinesReplaced, $desc );
		foreach ( $result as $r ) {
			$this->assertEquals( 'obsolete-tag', $r['type'], $desc );
		}

		$desc = "should not flag Tidy font fixups when Tidy does not do the fixups";
		$wtLines2 = [
			"<font color='green'></font>", // Regression test for T179757
			"<font color='green'>[[Foo]][[Bar]]</font>",
			"<font color='green'> [[Foo]]</font>",
			"<font color='green'>[[Foo]] </font>",
			"<font color='green'>[[Foo]]D</font>",
			"<font color='green'>''[[Foo|bar]]''</font>",
			"<font color='green'><span>[[Foo|bar]]</span></font>",
			"<font color='green'><div>[[Foo|bar]]</div></font>"
		];
		$n = count( $wtLines2 );
		$result = $this->wtToLint( implode( "\n", $wtLines2 ) );
		$this->assertCount( $n, $result, $desc );
		foreach ( $result as $r ) {
			$this->assertEquals( 'obsolete-tag', $r['type'], $desc );
		}
	}

	/**
	 * @covers \Wikimedia\Parsoid\Wt2Html\ParserPipeline
	 */
	public function testMultipleUnclosedFormatTags(): void {
		$desc = 'should detect multiple unclosed small tags';
		$result = $this->wtToLint( '<div><small>x</div><div><small>y</div>' );
		$this->assertCount( 3, $result, $desc );
		$this->assertEquals( 'multiple-unclosed-formatting-tags', $result[2]['type'], $desc );
		$this->assertEquals( 'small', $result[2]['params']['name'], $desc );

		$desc = 'should detect multiple unclosed big tags';
		$result = $this->wtToLint( '<div><big>x</div><div><big>y</div>' );
		$this->assertCount( 3, $result, $desc );
		$this->assertEquals( 'multiple-unclosed-formatting-tags', $result[2]['type'], $desc );
		$this->assertEquals( 'big', $result[2]['params']['name'], $desc );

		$desc = 'should detect multiple unclosed big tags';
		$result = $this->wtToLint( '<div><small><big><small><big>y</div>' );
		$this->assertCount( 5, $result, $desc );
		$this->assertEquals( 'multiple-unclosed-formatting-tags', $result[4]['type'], $desc );
		$this->assertEquals( 'small', $result[4]['params']['name'], $desc );

		$desc = 'should ignore unclosed small tags in tables';
		$this->noLintsOfThisType( $desc, "{|\n|<small>a\n|<small>b\n|}",
			'multiple-unclosed-formatting-tags' );

		$desc = 'should ignore unclosed small tags in tables but detect those outside it';
		$result = $this->wtToLint( "<small>x\n{|\n|<small>a\n|<small>b\n|}\n<small>y" );
		$this->assertCount( 5, $result, $desc );
		$this->assertEquals( 'multiple-unclosed-formatting-tags', $result[4]['type'], $desc );
		$this->assertEquals( 'small', $result[4]['params']['name'], $desc );

		$desc = 'should not flag undetected misnesting of formatting tags as " .
			"multiple unclosed formatting tags';
		$this->noLintsOfThisType( $desc, "<br><small>{{1x|<div>\n*item 1\n</div>}}</small>",
			'multiple-unclosed-formatting-tags' );

		$desc = "should detect Tidy's smart auto-fixup of paired unclosed formatting tags";
		$result = $this->wtToLint( '<b>foo<b>\n<code>foo <span>x</span> bar<code>' );
		$this->assertCount( 6, $result, $desc );
		$this->assertEquals( 'missing-end-tag', $result[0]['type'], $desc );
		$this->assertEquals( 'multiple-unclosed-formatting-tags', $result[1]['type'], $desc );
		$this->assertEquals( 'b', $result[1]['params']['name'], $desc );
		$this->assertEquals( 'missing-end-tag', $result[3]['type'], $desc );
		$this->assertEquals( 'multiple-unclosed-formatting-tags', $result[4]['type'], $desc );
		$this->assertEquals( 'code', $result[4]['params']['name'], $desc );

		$desc = "should not flag Tidy's smart auto-fixup of paired unclosed " .
			"formatting tags where Tidy won't do it";
		$this->noLintsOfThisType( $desc, "<b>foo <b>\n<code>foo <span>x</span> <!--comment--><code>",
			'multiple-unclosed-formatting-tags' );

		$desc = "should not flag Tidy's smart auto-fixup of paired unclosed tags for non-formatting tags";
		$this->noLintsOfThisType( $desc, "<span>foo<span>\n<div>foo <span>x</span> bar<div>",
			'multiple-unclosed-formatting-tags' );
	}

	/**
	 * @covers \Wikimedia\Parsoid\Wt2Html\ParserPipeline
	 */
	public function testUnclosedIBTagsInHeadings(): void {
		$desc = "should detect unclosed wikitext i tags in headings";
		$result = $this->wtToLint( "==foo<span>''a</span>==\nx" );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'unclosed-quotes-in-heading', $result[0]['type'], $desc );
		$this->assertEquals( 'i', $result[0]['params']['name'], $desc );
		$this->assertEquals( 'h2', $result[0]['params']['ancestorName'], $desc );

		$desc = "should detect unclosed wikitext b tags in headings";
		$result = $this->wtToLint( "==foo<span>'''a</span>==\nx" );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'unclosed-quotes-in-heading', $result[0]['type'], $desc );
		$this->assertEquals( 'b', $result[0]['params']['name'], $desc );
		$this->assertEquals( 'h2', $result[0]['params']['ancestorName'], $desc );

		$desc = "should detect unclosed HTML i/b tags in headings as missing-end-tag-in-heading";
		$result = $this->wtToLint( "==foo<span><i>a</span>==\nx\n==foo<span><b>a</span>==\ny" );
		$this->assertCount( 2, $result, $desc );
		$this->assertEquals( 'missing-end-tag-in-heading', $result[0]['type'], $desc );
		$this->assertEquals( 'missing-end-tag-in-heading', $result[1]['type'], $desc );
	}

	/**
	 * @covers \Wikimedia\Parsoid\Wt2Html\ParserPipeline
	 */
	public function testMultilineHtmlTablesInLists(): void {
		$desc = "should detect multiline HTML tables in lists (li)";
		$result = $this->wtToLint( "* <table><tr><td>x</td></tr>\n</table>" );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'multiline-html-table-in-list', $result[0]['type'], $desc );
		$this->assertEquals( 'table', $result[0]['params']['name'], $desc );
		$this->assertEquals( 'li', $result[0]['params']['ancestorName'], $desc );

		$desc = "should detect multiline HTML tables in lists (table in div)";
		$result = $this->wtToLint( "* <div><table><tr><td>x</td></tr>\n</table></div>" );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'multiline-html-table-in-list', $result[0]['type'], $desc );
		$this->assertEquals( 'table', $result[0]['params']['name'], $desc );
		$this->assertEquals( 'li', $result[0]['params']['ancestorName'], $desc );

		$desc = "should detect multiline HTML tables in lists (dt)";
		$result = $this->wtToLint( "; <table><tr><td>x</td></tr>\n</table>" );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'multiline-html-table-in-list', $result[0]['type'], $desc );
		$this->assertEquals( 'table', $result[0]['params']['name'], $desc );
		$this->assertEquals( 'dt', $result[0]['params']['ancestorName'], $desc );

		$desc = "should detect multiline HTML tables in lists (dd)";
		$result = $this->wtToLint( ": <table><tr><td>x</td></tr>\n</table>" );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'multiline-html-table-in-list', $result[0]['type'], $desc );
		$this->assertEquals( 'table', $result[0]['params']['name'], $desc );
		$this->assertEquals( 'dd', $result[0]['params']['ancestorName'], $desc );

		$desc = "should not detect multiline HTML tables in HTML lists";
		$this->expectEmptyResults( $desc, "<ul><li><table>\n<tr><td>x</td></tr>\n</table>\n</li></ul>" );

		$desc = "should not detect single-line HTML tables in lists";
		$this->expectEmptyResults( $desc, "* <div><table><tr><td>x</td></tr></table></div>" );

		$desc = "should not detect multiline HTML tables in ref tags";
		$this->expectEmptyResults( $desc,
			"a <ref><table>\n<tr><td>b</td></tr>\n</table></ref> <references />" );
	}

	/**
	 * @covers \Wikimedia\Parsoid\Wt2Html\ParserPipeline
	 */
	public function testDivSpanFlipTidyBug(): void {
		$desc = "should not trigger this lint when there are no style or class attributes";
		$this->expectEmptyResults( $desc, "<span><div>x</div></span>" );

		$desc = "should trigger this lint when there is a style or class attribute (1)";
		$result = $this->wtToLint( "<span class='x'><div>x</div></span>" );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'misc-tidy-replacement-issues', $result[0]['type'], $desc );
		$this->assertEquals( 'div-span-flip', $result[0]['params']['subtype'], $desc );

		$desc = "should trigger this lint when there is a style or class attribute (2)";
		$result = $this->wtToLint( "<span style='x'><div>x</div></span>" );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'misc-tidy-replacement-issues', $result[0]['type'], $desc );
		$this->assertEquals( 'div-span-flip', $result[0]['params']['subtype'], $desc );

		$desc = "should trigger this lint when there is a style or class attribute (3)";
		$result = $this->wtToLint( "<span><div class='x'>x</div></span>" );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'misc-tidy-replacement-issues', $result[0]['type'], $desc );
		$this->assertEquals( 'div-span-flip', $result[0]['params']['subtype'], $desc );

		$desc = "should trigger this lint when there is a style or class attribute (4)";
		$result = $this->wtToLint( "<span><div style='x'>x</div></span>" );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'misc-tidy-replacement-issues', $result[0]['type'], $desc );
		$this->assertEquals( 'div-span-flip', $result[0]['params']['subtype'], $desc );
	}

	/**
	 * @covers \Wikimedia\Parsoid\Wt2Html\ParserPipeline
	 */
	public function testWikilinkInExternalLink(): void {
		$desc = "should lint wikilink in external link correctly";
		$result = $this->wtToLint( "[http://google.com This is [[Google]]'s search page]" );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'wikilink-in-extlink', $result[0]['type'], $desc );
		$this->assertEquals( [ 0, 52, 19, 1 ], $result[0]['dsr'], $desc );

		$desc = "should lint wikilink in external link correctly";
		$result = $this->wtToLint(
			"[http://stackexchange.com is the official website for [[Stack Exchange]]]" );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'wikilink-in-extlink', $result[0]['type'], $desc );
		$this->assertEquals( [ 0, 73, 26, 1 ], $result[0]['dsr'], $desc );

		$desc = "should lint wikilink in external link correctly";
		$result = $this->wtToLint(
			"{{1x|foo <div> and [http://google.com [[Google]] bar] baz </div>}}" );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'wikilink-in-extlink', $result[0]['type'], $desc );
		$this->assertEquals( [ 0, 66, null, null ], $result[0]['dsr'], $desc );
		$this->assertTrue( isset( $result[0]['templateInfo'] ), $desc );
		$this->assertEquals( '1x', $result[0]['templateInfo']['name'], $desc );

		$desc = "should lint wikilink set in italics in external link correctly";
		$result = $this->wtToLint(
			"[http://stackexchange.com is the official website for ''[[Stack Exchange]]'']" );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'wikilink-in-extlink', $result[0]['type'], $desc );
		$this->assertEquals( [ 0, 77, 26, 1 ], $result[0]['dsr'], $desc );

		$desc = "should lint wikilink set in bold in external link correctly";
		$result = $this->wtToLint(
			"[http://stackexchange.com is the official website for '''[[Stack Exchange]]''']" );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'wikilink-in-extlink', $result[0]['type'], $desc );
		$this->assertEquals( [ 0, 79, 26, 1 ], $result[0]['dsr'], $desc );

		$desc = "should lint wikilink set in italics and bold in external link correctly";
		$result = $this->wtToLint(
			"[http://stackexchange.com is the official website for '''''[[Stack Exchange]]''''']" );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'wikilink-in-extlink', $result[0]['type'], $desc );
		$this->assertEquals( [ 0, 83, 26, 1 ], $result[0]['dsr'], $desc );

		$desc = "should lint figure wikilink in external link correctly";
		$result = $this->wtToLint(
			"[http://foo.bar/some.link [[File:Foobar.jpg|scale=0.5]] image]" );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'wikilink-in-extlink', $result[0]['type'], $desc );
		$this->assertEquals( [ 0, 62, 26, 1 ], $result[0]['dsr'], $desc );

		$desc = "should lint image wikilink in external link correctly";
		$result = $this->wtToLint(
			"[http://foo.bar/other.link [[File:Foobar.jpg|thumb]] image]" );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'wikilink-in-extlink', $result[0]['type'], $desc );
		$this->assertEquals( [ 0, 59, 27, 1 ], $result[0]['dsr'], $desc );

		$desc = "should lint image wikilink in external link with |link= correctly";
		$result = $this->wtToLint(
			"[http://foo.bar/other.link [[File:Foobar.jpg|link=]] image]" );
		$this->assertCount( 0, $result, $desc );

		$desc = "should not generate lint error for image wikilink following external link";
		$result = $this->wtToLint(
			"[http://foo.bar/other.link testing123][[File:Foobar.jpg]]" );
		$this->assertCount( 0, $result, $desc );

		$desc = "should lint audio wikilink in external link correctly";
		$result = $this->wtToLint(
			"[http://foo.bar/other.link [[File:Audio.oga]] audio]" );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'wikilink-in-extlink', $result[0]['type'], $desc );
		$this->assertEquals( [ 0, 52, 27, 1 ], $result[0]['dsr'], $desc );

		$desc = "should lint video wikilink in external link correctly";
		$result = $this->wtToLint(
			"[http://foo.bar/other.link [[File:Video.ogv]] video]" );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'wikilink-in-extlink', $result[0]['type'], $desc );
		$this->assertEquals( [ 0, 52, 27, 1 ], $result[0]['dsr'], $desc );

		$desc = "should lint audio wikilink with preceding text in external link correctly";
		$result = $this->wtToLint(
			"[http://foo.bar/other.link first_child [[File:Audio.oga]] audio]" );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'wikilink-in-extlink', $result[0]['type'], $desc );
		$this->assertEquals( [ 0, 64, 27, 1 ], $result[0]['dsr'], $desc );

		$desc = "should lint video wikilink with prededing bold text in external link correctly";
		$result = $this->wtToLint(
			"[http://foo.bar/other.link '''first_child''' [[File:Video.ogv]] video]" );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'wikilink-in-extlink', $result[0]['type'], $desc );
		$this->assertEquals( [ 0, 70, 27, 1 ], $result[0]['dsr'], $desc );

		$desc = "should lint audio wikilink in extlink followed by span correctly";
		$result = $this->wtToLint(
			"[http://foo.bar/other.link [[File:Audio.oga]] audio]<span>this is an Element</span>" );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'wikilink-in-extlink', $result[0]['type'], $desc );
		$this->assertEquals( [ 0, 52, 27, 1 ], $result[0]['dsr'], $desc );

		$desc = "should lint audio wikilink in extlink with preceding text followed by span correctly";
		$result = $this->wtToLint(
			"[http://foo.bar/other.link text content [[File:Audio.oga]] audio]<span>this is an Element</span>" );
		$this->assertCount( 1, $result, $desc );
		$this->assertEquals( 'wikilink-in-extlink', $result[0]['type'], $desc );
		$this->assertEquals( [ 0, 65, 27, 1 ], $result[0]['dsr'], $desc );
	}

	/**
	 * Provide test cases for large tables
	 * @return array[]
	 */
	public function provideLargeTablesTests(): array {
		$noLongRowsTable = [
			"{|",
			"|-",
			"! Header 1 !! Header 2 !! Header 3",
		];
		for ( $i = 0; $i < 30; $i++ ) {
			$noLongRowsTable[] = "|-";
			$noLongRowsTable[] = "| Cell 1 || Cell 2 || Cell 3";
		}
		// Make the last row "large" by adding 3 more columns
		$nonUniformLongRowTable = $noLongRowsTable;
		$nonUniformLongRowTable[] = "|| Cell 4 || Cell 5 || Cell 6";

		$noLongRowsTable[] = "|}";
		$nonUniformLongRowTable[] = "|}";
		return [
			'empty table should not cause crashers' => [
				'wikiTextLines' => [
					"{|",
					"|}"
				],
				'columnCount' => 4
				// No dsr => no lints found
			],
			'6 header columns' => [
				'wikiTextLines' => [
					"{|",
					"|-",
					"! Header 1 !! Header 2 !! Header 3 !! Header 4 !! Header 5 !! Header 6",
					"|-",
					"| Cell 1 || Cell 2 || Cell 3",
					"|}"
				],
				'columnCount' => 6,
				'dsr' => [ 0, 111, 2, 2 ]
			],
			'3 header columns and 6 row columns' => [
				'wikiTextLines' => [
					"{|",
					"|-",
					"! Header 1 !! Header 2 !! Header 3",
					"|-",
					"| Cell 1 || Cell 2 || Cell 3 || Cell 4 || Cell 5 || Cell 6",
					"|}"
				],
				'columnCount' => 6,
				'dsr' => [ 0, 105, 2, 2 ]
			],
			'3 header columns and 3 row columns and 6 row columns' => [
				'wikiTextLines' => [
					"{|",
					"|-",
					"! Header 1 !! Header 2 !! Header 3",
					"|-",
					"| Cell 1 || Cell 2 || Cell 3",
					"|-",
					"| Cell 1 || Cell 2 || Cell 3 || Cell 4 || Cell 5 || Cell 6",
					"|}"
				],
				'columnCount' => 6,
				'dsr' => [ 0, 137, 2, 2 ]
			],
			'3 row columns and 6 row columns' => [
				'wikiTextLines' => [
					"{|",
					"|-",
					"| Cell 1 || Cell 2 || Cell 3",
					"|-",
					"| Cell 1 || Cell 2 || Cell 3 || Cell 4 || Cell 5 || Cell 6",
					"|}"
				],
				'columnCount' => 6,
				'dsr' => [ 0, 99, 2, 2 ]
			],
			'detect and exit on the first lint result' => [
				'wikiTextLines' => [
					"{|",
					"|-",
					"| Cell 1 || Cell 2 || Cell 3 || Cell 4 || Cell 5 || Cell 6",
					"|-",
					"| Cell 1 ",
					"|-",
					"| Cell 1 || Cell 2 || Cell 3 || Cell 4 || Cell 5 || Cell 6 || Cell 7 || Cell 8",
					"|}"
				],
				'columnCount' => 6,
				'dsr' => [ 0, 162, 2, 2 ]
			],
			'3 and 4 headers intermixed with table rows and but 8 row columns ' => [
				'wikiTextLines' => [
					"{|",
					"|-",
					"| Cell 1 || Cell 2",
					"|-",
					"! Header 1 !! Header 2 !! Header 3",
					"|-",
					"| Cell 1 || Cell 2 || Cell 3",
					"|-",
					"! Header 1 !! Header 2 !! Header 3 !! Header 4",
					"|-",
					"| Cell 1 || Cell 2 || Cell 3 || Cell 4 || Cell 5 || Cell 6 || Cell 7 || Cell 8",
					"|}"
				],
				'columnCount' => 8,
				'dsr' => [ 0, 229, 2, 2 ]
			],
			'acceptable table size' => [
				'wikiTextLines' => [
					"{|",
					"|-",
					"| Cell 1 || Cell 2",
					"|-",
					"! Header 1 !! Header 2 !! Header 3",
					"|-",
					"| Cell 1 || Cell 2 || Cell 3 || Cell 4 || Cell 5",
					"|-",
					"! Header 1 !! Header 2",
					"|-",
					"| Cell 1 || Cell 2 || Cell 3 || Cell 4 || Cell 5",
					"|}"
				],
				'columnCount' => 5
			],
			'acceptable table size, rows on multiple wikitext lines' => [
				'wikiTextLines' => [
					"{|",
					"|-",
					"! Header 1 !! Header 2 ",
					"!! Header 3 !! Header 4",
					"|-",
					"| Cell 1 || Cell 2",
					"|| Cell 3 || Cell 4",
					"|}"
				],
				'columnCount' => 4
				// No dsr => no lints found
			],
			'6 header columns and 6 row columns, rows on multiple wikitext lines' => [
				'wikiTextLines' => [
					"{|",
					"|-",
					"! Header 1 !! Header 2 ",
					"!! Header 3 !! Header 4",
					"!! Header 5 !! Header 6",
					"|-",
					"| Cell 1 || Cell 2",
					"|| Cell 3 || Cell 4",
					"|| Cell 5 || Cell 6",
					"|}"
				],
				'columnCount' => 6,
				'dsr' => [ 0, 142, 2, 2 ]
			],
			'Edge Case' => [
				'wikiTextLines' => [
					"{|",
					"|-",
					"|a",
					"|-",
					"|b",
					"|-",
					"|c",
					"|-",
					"|d",
					"|-",
					"|e",
					"|-",
					"|f",
					"|-",
					"| a || b || c || d || e ||f",
					"|}"
				],
				'columnCount' => 6,
				'dsr' => [ 0, 72, 2, 2 ]
			],
			'transclusions 5 header columns and 6 row columns' => [
				'wikiTextLines' => [
					"{{1x|",
					"<table>",
					"<tr>",
					"<th>Header 1</th>",
					"<th>Header 2</th>",
					"<th>Header 3</th>",
					"<th>Header 4</th>",
					"<th>Header 5</th>",
					"</tr>",
					"<tr>",
					"<td>Cell 1</td>",
					"<td>Cell 2</td>",
					"<td>Cell 3</td>",
					"<td>Cell 4</td>",
					"<td>Cell 5</td>",
					"<td>Cell 6</td>",
					"</tr>",
					"</table>",
					"}}"
				],
				'columnCount' => 6,
				'dsr' => [ 0, 233, null, null ],
				'templateName' => '1x'
			],
			'transclusions 6 header columns and 5 row columns' => [
				'wikiTextLines' => [
					"{{1x|",
					"<table>",
					"<tr>",
					"<th>Header 1</th>",
					"<th>Header 2</th>",
					"<th>Header 3</th>",
					"<th>Header 4</th>",
					"<th>Header 5</th>",
					"<th>Header 6</th>",
					"</tr>",
					"<tr>",
					"<td>Cell 1</td>",
					"<td>Cell 2</td>",
					"<td>Cell 3</td>",
					"<td>Cell 4</td>",
					"<td>Cell 5</td>",
					"</tr>",
					"</table>",
					"}}"
				],
				'columnCount' => 6,
				'dsr' => [ 0, 235, null, null ],
				'templateName' => '1x'
			],
			'transclusions 5 header columns and 5 row columns' => [
				'wikiTextLines' => [
					"{{1x|",
					"<table>",
					"<tr>",
					"<th>Header 1</th>",
					"<th>Header 2</th>",
					"<th>Header 3</th>",
					"<th>Header 4</th>",
					"<th>Header 5</th>",
					"</tr>",
					"<tr>",
					"<td>Cell 1</td>",
					"<td>Cell 2</td>",
					"<td>Cell 3</td>",
					"<td>Cell 4</td>",
					"<td>Cell 5</td>",
					"</tr>",
					"</table>",
					"}}"
				],
				'columnCount' => 5,
				'dsr' => [], // empty dsr => no lints found
				'templateName' => '1x'
			],
			'long rows 30' => [
				'wikiTextLines' => $noLongRowsTable,
				'columnCount' => 3,
				'dsr' => [] // empty dsr => no lints found
			],
			// An exhaustive table search heuristic will flag a lint here.
			// But, a more performant search that only looks at the first
			// N (=10 right now) rows will not find a lint here
			'non-uniform table with a long row beyond the first 10' => [
				'wikiTextLines' => $nonUniformLongRowTable,
				'columnCount' => 3,
				'dsr' => [] // empty dsr => no lints found
			],
			'Nested Table 1' => [
				'wikiTextLines' => [
					"{|",
					"|+",
					"!Header 1",
					"!Header 2",
					"!Header 3",
					"|-",
					"|Cell 1",
					"|Cell 2",
					"|Cell 3",
					"|-",
					"|",
					"{|",
					"|+",
					"!Header 4",
					"!Header 5",
					"!Header 6",
					"|-",
					"|Cell 4",
					"|Cell 5",
					"|Cell 6",
					"|}",
					"|",
					"|",
					"|",
					"{|",
					"|+",
					"!Header 7",
					"!Header 8",
					"!Header 9",
					"|-",
					"|Cell 7",
					"|Cell 8",
					"|Cell 9",
					"|}",
					"|}",
				],
				'columnCount' => 1,
				'dsr' => [] // empty dsr => no lints found
			],
			'Nested Table 2' => [
				'wikiTextLines' => [
					"{|",
					"|+",
					"!Header 1",
					"!Header 2",
					"!Header 3",
					"|-",
					"|Cell 1",
					"|Cell 2",
					"|Cell 3",
					"|-",
					"|",
					"{|",
					"|+",
					"!Header 4",
					"!Header 5",
					"!Header 6",
					"|-",
					"|Cell 4",
					"|Cell 5",
					"|Cell 6",
					"|}",
					"|",
					"|",
					"|",
					"{|",
					"|+",
					"!Header 7",
					"!Header 8",
					"!Header 9",
					"|-",
					"|Cell 7",
					"|Cell 8",
					"|Cell 9",
					"|Cell 10",
					"|Cell 11",
					"|Cell 12",
					"|}",
					"|}",
				],
				'columnCount' => 6,
				'dsr' => [ 140, 232, 2, 2 ]
			],
		];
	}

	/**
	 * @covers \Wikimedia\Parsoid\Wt2Html\ParserPipeline
	 *
	 * @param string[] $wikiTextLines
	 * @param int $columnCount
	 * @param array $dsr
	 * @param string|null $templateName
	 *
	 * @dataProvider provideLargeTablesTests
	 */
	public function testLargeTables( $wikiTextLines, $columnCount, $dsr = [], $templateName = null ): void {
		$opts = [];
		$siteConfig = new MockSiteConfig( $opts );
		$lintConfig = $siteConfig->getLinterConfig();
		$columnsMax = $lintConfig['maxTableColumnHeuristic'] ?? 0;

		$desc = 'should identify large width table for T334528';
		$result = $this->wtToLint( implode( "\n", $wikiTextLines ) );
		$expectedCount = count( $dsr ) < 1 ? 0 : 1;
		$this->assertCount( $expectedCount, $result, $desc );
		if ( $expectedCount < 1 ) {
			return;
		}
		$this->assertEquals( 'large-tables', $result[0]['type'], $desc );
		$this->assertTrue( isset( $result[0]['params'] ), $desc );
		$this->assertEquals( $columnCount, $result[0]['params']['columns'], $desc );
		$this->assertEquals( $columnsMax, $result[0]['params']['columnsMax'], $desc );
		$this->assertEquals( $dsr, $result[0]['dsr'], $desc );
		if ( $templateName ) {
			$this->assertTrue( isset( $result[0]['templateInfo'] ), $desc );
			$this->assertEquals( $templateName, $result[0]['templateInfo']['name'], $desc );
		}
	}

	/**
	 * @see testLogInlineBackgroundWithoutColor
	 * @see https://phabricator.wikimedia.org/T358238
	 *
	 * Provides test cases for the 'night-mode-unaware-background-color' lint
	 * We are skipping DSR checks for this lint since:
	 * - DSR functionality is shared with all lints
	 * - that DSR functionality is adequately tested in other lints
	 * - there is nothing unusual DSR-wise about this lint that
	 *   needs special verification.
	 */
	public function provideLogInlineBackgroundWithoutColor(): array {
		return [
			[
				'should not lint when style attribute is missing',
				'<div></div>',
				false,
			],
			[
				'should lint when background color is present but font color is missing',
				'<div style="background-color: red;"></div>',
				true,
			],
			[
				'should lint when background-color is present but font color is missing in a template',
				'{{1x|<div style="background-color: red;"></div>}}',
				true,
				"1x"
			],
			[
				'should lint when background is present but font color is missing',
				'<div style="background: green;"></div>',
				true,
			],
			[
				'should not have lint any issues when both background color and font color are present',
				'<div style="background-color: red; color: black;"></div>',
				false,
			],
			[
				'should not lint when font color is present but background color is missing',
				'<div style="color: red;"></div>',
				false,
			],
			[
				'should not lint when both background color and font color are present,' .
				' even though they are missing spaces',
				'<div style="background-color:black;color:blue;"></div>' .
				'<div style="color:blue;background-color:black;"></div>',
				false,
			],
		];
	}

	/**
	 * @covers \Wikimedia\Parsoid\Wt2Html\ParserPipeline
	 * @covers \Wikimedia\Parsoid\Wt2Html\PP\Processors\Linter::lintNightModeUnawareBackgroundColor
	 * @see https://phabricator.wikimedia.org/T358238
	 * @dataProvider provideLogInlineBackgroundWithoutColor
	 */
	public function testLogInlineBackgroundWithoutColor(
		string $desc, string $wikitext, bool $haveLint, ?string $templateName = null
	): void {
		if ( !$haveLint ) {
			$this->expectEmptyResults( $desc, $wikitext );
		} else {
			$result = $this->wtToLint( $wikitext );
			$this->assertCount( 1, $result, $desc );
			$this->assertEquals( 'night-mode-unaware-background-color', $result[0]['type'], $desc );
			if ( $templateName ) {
				$this->assertTrue( isset( $result[0]['templateInfo'] ), $desc );
				$this->assertEquals( $templateName, $result[0]['templateInfo']['name'], $desc );
			}
		}
	}
}

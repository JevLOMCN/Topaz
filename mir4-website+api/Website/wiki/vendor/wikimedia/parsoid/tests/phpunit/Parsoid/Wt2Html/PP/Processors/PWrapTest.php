<?php

namespace Test\Parsoid\Wt2Html\PP\Processors;

use PHPUnit\Framework\TestCase;
use Wikimedia\Parsoid\Mocks\MockEnv;
use Wikimedia\Parsoid\Utils\ContentUtils;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMDataUtils;
use Wikimedia\Parsoid\Wt2Html\PP\Processors\PWrap;

// phpcs:disable Generic.Files.LineLength.TooLong

/**
 * based on tests/mocha/pwrap.js
 * @coversDefaultClass \Wikimedia\Parsoid\Wt2Html\PP\Processors\PWrap
 */
class PWrapTest extends TestCase {

	private function verifyPWrap( string $html, string $expected ): void {
		$mockEnv = new MockEnv( [] );
		$doc = ContentUtils::createAndLoadDocument( $html );
		$body = DOMCompat::getBody( $doc );
		$pwrap = new PWrap();
		$pwrap->run( $mockEnv, $body );

		$innerHtml = DOMCompat::getInnerHTML( $body );
		$pattern = '/ ' . DOMDataUtils::DATA_OBJECT_ATTR_NAME . '="\d+"/';
		$actual = preg_replace( $pattern, '', $innerHtml );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @covers ::run
	 * @dataProvider provideNoPWrapper
	 * @param string $html
	 * @param string $expected
	 */
	public function testNoPWrapper( string $html, string $expected ): void {
		$this->verifyPWrap( $html, $expected );
	}

	public function provideNoPWrapper() {
		// NOTE: verifyPWrap doesn't store data attribs. Hence no data-parsoid in output.
		return [
			[ '', '' ],
			[ ' ', ' ' ],
			[ ' <!--c--> ', ' <!--c--> ' ],
			[
				// "empty" span gets no p-wrapper
				'<span about="#mwt1" data-parsoid=\'{"tmp":{"tagId":null,"bits":8}}\'><!--x--></span>',
				'<span about="#mwt1"><!--x--></span>'
			],
			[
				// "empty" span gets no p-wrapper
				'<style>p{}</style><span about="#mwt1" data-parsoid=\'{"tmp":{"tagId":null,"bits":8}}\'><!--x--></span>',
				'<style>p{}</style><span about="#mwt1"><!--x--></span>'
			],
			[ '<div>a</div>', '<div>a</div>' ],
			[ '<div>a</div> <div>b</div>', '<div>a</div> <div>b</div>' ],
			[ '<i><div>a</div></i>', '<i><div>a</div></i>' ],
			// <span> is not a splittable tag
			[ '<span>x<div>a</div>y</span>', '<span>x<div>a</div>y</span>' ],
			[ '<span>x<div></div>y</span>', '<span>x<div></div>y</span>' ],
		];
	}

	/**
	 * @covers ::run
	 * @dataProvider provideSimplePWrapper
	 * @param string $html
	 * @param string $expected
	 */
	public function testSimplePWrapper( string $html, string $expected ): void {
		$this->verifyPWrap( $html, $expected );
	}

	public function provideSimplePWrapper(): array {
		return [
			[ 'a', '<p>a</p>' ],
			// <span> is not a splittable tag, but gets p-wrapped in simple wrapping scenarios
			[ '<span>a</span>', '<p><span>a</span></p>' ],
			[
				'x <div>a</div> <div>b</div> y',
				'<p>x </p><div>a</div> <div>b</div><p> y</p>',
			],
			[
				'x<!--c--> <div>a</div> <div>b</div> <!--c-->y',
				'<p>x<!--c--> </p><div>a</div> <div>b</div> <!--c--><p>y</p>',
			],
		];
	}

	/**
	 * @covers ::run
	 * @dataProvider provideComplexPWrapper
	 * @param string $html
	 * @param string $expected
	 */
	public function testComplexPWrapper( string $html, string $expected ): void {
		$this->verifyPWrap( $html, $expected );
	}

	public function provideComplexPWrapper(): array {
		return [
			[
				'<i>x<div>a</div>y</i>',
				'<p><i>x</i></p><i><div>a</div></i><p><i>y</i></p>',
			],
			[
				'a<small>b</small><i>c<div>d</div>e</i>f',
				'<p>a<small>b</small><i>c</i></p><i><div>d</div></i><p><i>e</i>f</p>',
			],
			[
				'a<small>b<i>c<div>d</div></i>e</small>',
				'<p>a<small>b<i>c</i></small></p><small><i><div>d</div></i></small><p><small>e</small></p>',
			],
			[
				'x<small><div>y</div></small>',
				'<p>x</p><small><div>y</div></small>',
			],
			[
				'a<small><i><div>d</div></i>e</small>',
				'<p>a</p><small><i><div>d</div></i></small><p><small>e</small></p>',
			],
			[
				'<i>a<div>b</div>c<b>d<div>e</div>f</b>g</i>',
				'<p><i>a</i></p><i><div>b</div></i><p><i>c<b>d</b></i></p>' .
					'<i><b><div>e</div></b></i><p><i><b>f</b>g</i></p>',
			],
			[
				'<i><b><font><div>x</div></font></b><div>y</div><b><font><div>z</div></font></b></i>',
				'<i><b><font><div>x</div></font></b><div>y</div><b><font><div>z</div></font></b></i>',
			],
			[
				// Wikitext: "<div>foo</div> {{1x|a}}</span>"
				// NOTE: Simplified the strippedTag meta tag by removing data-parsoid since that is immaterial to the test
				'<div>foo</div> <meta typeof="mw:Transclusion" about="#mwt1"/>a<meta typeof="mw:Transclusion/End" about="#mwt1"/><meta typeof="mw:Placeholder/StrippedTag"/>',
				'<div>foo</div> <meta typeof="mw:Transclusion" about="#mwt1"/><p>a</p><meta typeof="mw:Transclusion/End" about="#mwt1"/><meta typeof="mw:Placeholder/StrippedTag"/>',
			],
			[
				// Wikitext: "<div>foo</div> {{1x|a}} <div>bar</div>"
				'<div>foo</div> <meta typeof="mw:Transclusion" about="#mwt1"/>a<meta typeof="mw:Transclusion/End" about="#mwt1"/> <div>bar</div>',
				'<div>foo</div> <meta typeof="mw:Transclusion" about="#mwt1"/><p>a</p><meta typeof="mw:Transclusion/End" about="#mwt1"/> <div>bar</div>',
			],
			[
				// Wikitext: "<div>foo</div> a {{1x|b}} <div>bar</div>
				'<div>foo</div> a <meta typeof="mw:Transclusion" about="#mwt2"/>b<meta typeof="mw:Transclusion/End" about="#mwt2"/> <div>bar</div>',
				'<div>foo</div><p> a <meta typeof="mw:Transclusion" about="#mwt2"/>b<meta typeof="mw:Transclusion/End" about="#mwt2"/></p> <div>bar</div>',
			],
			[
				// This is an example where ideally the opening meta tag will be pushed into the <p> tag
				// but the algorithm isn't smart enough for doing that.
				// Wikitext: "<div>foo</div> {{1x|a}} b <div>bar</div>"
				'<div>foo</div> <meta typeof="mw:Transclusion" about="#mwt1"/>a<meta typeof="mw:Transclusion/End" about="#mwt1"/> b <div>bar</div>',
				'<div>foo</div> <meta typeof="mw:Transclusion" about="#mwt1"/><p>a<meta typeof="mw:Transclusion/End" about="#mwt1"/> b </p><div>bar</div>',
			],
			[
				// Wikitext: "<div>foo</div> a {{1x|b}} {{1x|<div>bar</div>}}"
				'<div>foo</div> a <meta typeof="mw:Transclusion" about="#mwt1"/>b<meta typeof="mw:Transclusion/End" about="#mwt1"/> <meta typeof="mw:Transclusion" about="#mwt2"/><div>bar</div><meta typeof="mw:Transclusion/End" about="#mwt2"/>',
				'<div>foo</div><p> a <meta typeof="mw:Transclusion" about="#mwt1"/>b<meta typeof="mw:Transclusion/End" about="#mwt1"/></p> <meta typeof="mw:Transclusion" about="#mwt2"/><div>bar</div><meta typeof="mw:Transclusion/End" about="#mwt2"/>',
			],
		];
	}
}

<?php

namespace Test\Parsoid\Utils;

use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMUtils;

/**
 * @coversDefaultClass  \Wikimedia\Parsoid\Utils\DOMUtils
 */
class DOMUtilsTest extends \PHPUnit\Framework\TestCase {

	/**
	 * Test node properties methods.
	 *
	 * @covers ::isRemexBlockNode
	 * @covers ::isWikitextBlockNode
	 * @covers ::isFormattingElt
	 * @covers ::isQuoteElt
	 * @covers ::isBody
	 * @covers ::isRemoved
	 * @covers ::isHeading
	 * @covers ::isList
	 * @covers ::isListItem
	 * @covers ::isListOrListItem
	 * @covers ::isNestedInListItem
	 * @covers ::hasElementChild
	 * @covers ::hasBlockElementDescendant
	 * @covers ::isIEW
	 * @covers ::allChildrenAreTextOrComments
	 * @covers ::isTableTag
	 * @covers ::isRawTextElement
	 * @covers ::hasBlockTag
	 * @dataProvider provideNodeProperties
	 */
	public function testNodeProperties( string $html, array $props ) {
		$doc = DOMUtils::parseHTML( $html );
		$sel = $props['selector'] ?? null;
		if ( $sel ) {
			$node = DOMCompat::querySelector( $doc, $sel );
		} else {
			$node = DOMCompat::getBody( $doc )->firstChild;
		}

		$methods = [
			// This method doesn't match the naming pattern:
			'allChildrenAreTextOrComments',
			'hasBlockElementDescendant',
			'hasBlockTag',
			'hasElementChild',
			'isBody',
			'isFormattingElt',
			'isHeading',
			'isIEW',
			'isList',
			'isListItem',
			'isListOrListItem',
			'isNestedInListItem',
			'isQuoteElt',
			'isRawTextElement',
			'isRemexBlockNode',
			'isRemoved',
			'isTableTag',
			'isWikitextBlockNode',
		];
		$expected = [];
		$actual = [];
		foreach ( $methods as $m ) {
			if ( $props[$m] ?? false ) {
				$expected[$m] = true;
			}
			if ( DOMUtils::$m( $node ) ) {
				$actual[$m] = true;
			}
		}
		$this->assertSame( $expected, $actual );
	}

	public function provideNodeProperties() {
		return [
			[ '<a href="xyz">foo<!--bar--></a>', [
				'allChildrenAreTextOrComments' => true,
				'isFormattingElt' => true,
			] ],
			[ '<abbr>FOO</abbr>', [
				'allChildrenAreTextOrComments' => true,
			] ],
			[ '<b>bold</b>', [
				'allChildrenAreTextOrComments' => true,
				'isFormattingElt' => true,
				'isQuoteElt' => true,
			] ],
			[ '<h1>heading1</h1>', [
				'allChildrenAreTextOrComments' => true,
				'hasBlockTag' => true,
				'isHeading' => true,
				'isRemexBlockNode' => true,
				'isWikitextBlockNode' => true,
			] ],
			[ '<h2>heading<b>bold</b></h2>', [
				'hasBlockTag' => true,
				'hasElementChild' => true,
				'isHeading' => true,
				'isRemexBlockNode' => true,
				'isWikitextBlockNode' => true,
			] ],
			[ '<body>foo<!--bar-->bat</body>', [
				'selector' => 'body',
				'allChildrenAreTextOrComments' => true,
				'hasBlockTag' => true,
				'isBody' => true,
				'isRemexBlockNode' => true,
			] ],
			[ " \t ", [
				'allChildrenAreTextOrComments' => true,
				'isIEW' => true,
			] ],
			[ "<!--comment-->", [
				'allChildrenAreTextOrComments' => true,
			] ],
			[ "<ol><li>foo<b>bar", [
				'hasBlockElementDescendant' => true,
				'hasBlockTag' => true,
				'hasElementChild' => true,
				'isList' => true,
				'isListOrListItem' => true,
				'isRemexBlockNode' => true,
				'isWikitextBlockNode' => true,
			] ],
			[ "<ol><li>foo<b>bar", [
				'selector' => 'li',
				'hasBlockTag' => true,
				'hasElementChild' => true,
				'isListItem' => true,
				'isListOrListItem' => true,
				'isRemexBlockNode' => true,
				'isWikitextBlockNode' => true,
			] ],
			[ "<ol><li>foo<b>bar", [
				'selector' => 'b',
				'allChildrenAreTextOrComments' => true,
				'isFormattingElt' => true,
				'isNestedInListItem' => true,
				'isQuoteElt' => true,
			] ],
			[ "<pre>preformatted<b>text</b></pre>", [
				'hasBlockTag' => true,
				'hasElementChild' => true,
				'isRemexBlockNode' => true,
				'isWikitextBlockNode' => true,
			] ],
			[ "<style>ignore <b> tag</style>", [
				'allChildrenAreTextOrComments' => true,
				'isRawTextElement' => true,
			] ],
			[ "<table><tr><td>foo</td></tr></table>", [
				'hasBlockElementDescendant' => true,
				'hasBlockTag' => true,
				'hasElementChild' => true,
				'isRemexBlockNode' => true,
				'isTableTag' => true,
				'isWikitextBlockNode' => true,
			] ],
			[ "<table><tr><td>foo</td></tr></table>", [
				'selector' => 'tr',
				'hasBlockElementDescendant' => true,
				'hasBlockTag' => true,
				'hasElementChild' => true,
				'isRemexBlockNode' => true,
				'isTableTag' => true,
				'isWikitextBlockNode' => true,
			] ],
			[ "<table><tr><td>foo</td></tr></table>", [
				'selector' => 'td',
				'allChildrenAreTextOrComments' => true,
				'hasBlockTag' => true,
				'isRemexBlockNode' => true,
				'isTableTag' => true,
				'isWikitextBlockNode' => true,
			] ],
		];
	}

	/**
	 * Test element properties methods.
	 *
	 * @covers ::assertElt
	 * @covers ::attributes
	 * @covers ::isMetaDataTag
	 * @dataProvider provideElementProperties
	 */
	public function testElementProperties( string $html, array $props ) {
		$doc = DOMUtils::parseHTML( $html );
		$sel = $props['selector'] ?? 'body > *';
		$node = DOMCompat::querySelector( $doc, $sel );
		DOMUtils::assertElt( $node );

		$methods = [
			'attributes',
			'isMetaDataTag',
		];
		$expected = [];
		$actual = [];
		foreach ( $methods as $m ) {
			$e = $props[$m] ?? false;
			if ( $e ) {
				$expected[$m] = $e;
			}
			$a = DOMUtils::$m( $node );
			if ( $a ) {
				$actual[$m] = $a;
			}
		}
		$this->assertSame( $expected, $actual );
	}

	public function provideElementProperties() {
		return [
			[ '<a href="xyz">foo<!--bar--></a>', [
				'attributes' => [ 'href' => 'xyz' ],
			] ],
			[ '<link rel="foo" />', [
				'attributes' => [ 'rel' => 'foo' ],
				'isMetaDataTag' => true,
			] ],
			[ '<base href="//foo/" />', [
				'attributes' => [ 'href' => '//foo/' ],
				'isMetaDataTag' => true,
			] ],
			[ '<meta name="foo" />', [
				'attributes' => [ 'name' => 'foo' ],
				'isMetaDataTag' => true,
			] ],
			[ '<span xmlns="test" class="foo">bar</span>', [
				'attributes' => [ 'xmlns' => 'test', 'class' => 'foo' ],
			] ],
		];
	}

	/**
	 * @covers ::addRel
	 * @covers ::addTypeOf
	 * @covers ::hasRel
	 * @covers ::hasTypeOf
	 * @covers ::matchRel
	 * @covers ::matchTypeOf
	 * @covers ::removeRel
	 * @covers ::removeTypeOf
	 * @dataProvider provideMultivalAttr
	 */
	public function testMultivalAttr( string $which ) {
		$addMethod = "add$which";
		$hasMethod = "has$which";
		$matchMethod = "match$which";
		$removeMethod = "remove$which";

		$doc = DOMUtils::parseHtml( '<span>' );
		$el = DOMCompat::querySelector( $doc, 'span' );

		$this->assertSame( false, DOMUtils::$hasMethod( $el, 'foo' ) );
		$this->assertSame( false, DOMUtils::$hasMethod( $el, 'bar' ) );
		$this->assertSame( false, DOMUtils::$hasMethod( $el, '0' ) );
		$this->assertSame( null, DOMUtils::$matchMethod( $el, '/^(fo|ba|0)/' ) );
		DOMUtils::$addMethod( $el, 'foo' );
		$this->assertSame( true, DOMUtils::$hasMethod( $el, 'foo' ) );
		$this->assertSame( false, DOMUtils::$hasMethod( $el, 'bar' ) );
		$this->assertSame( false, DOMUtils::$hasMethod( $el, '0' ) );
		$this->assertSame( 'foo', DOMUtils::$matchMethod( $el, '/^(fo|ba|0)/' ) );
		DOMUtils::$addMethod( $el, 'bar' );
		$this->assertSame( true, DOMUtils::$hasMethod( $el, 'foo' ) );
		$this->assertSame( true, DOMUtils::$hasMethod( $el, 'bar' ) );
		$this->assertSame( false, DOMUtils::$hasMethod( $el, '0' ) );
		$this->assertNotNull( DOMUtils::$matchMethod( $el, '/^(fo|ba|0)/' ) );
		DOMUtils::$addMethod( $el, '0' ); # check that a falsy value is ok
		$this->assertSame( true, DOMUtils::$hasMethod( $el, 'foo' ) );
		$this->assertSame( true, DOMUtils::$hasMethod( $el, 'bar' ) );
		$this->assertSame( true, DOMUtils::$hasMethod( $el, '0' ) );
		$this->assertNotNull( DOMUtils::$matchMethod( $el, '/^(fo|ba|0)/' ) );
		DOMUtils::$addMethod( $el, '' ); # should be a no-op
		$this->assertSame( true, DOMUtils::$hasMethod( $el, 'foo' ) );
		$this->assertSame( true, DOMUtils::$hasMethod( $el, 'bar' ) );
		$this->assertSame( true, DOMUtils::$hasMethod( $el, '0' ) );
		$this->assertNotNull( DOMUtils::$matchMethod( $el, '/^(fo|ba|0)/' ) );
		$this->assertSame( [ 'foo bar 0' ], array_values( DOMUtils::attributes( $el ) ) );
		DOMUtils::$addMethod( $el, ' ' ); # should be a no-op
		$this->assertSame( true, DOMUtils::$hasMethod( $el, 'foo' ) );
		$this->assertSame( true, DOMUtils::$hasMethod( $el, 'bar' ) );
		$this->assertSame( true, DOMUtils::$hasMethod( $el, '0' ) );
		$this->assertNotNull( DOMUtils::$matchMethod( $el, '/^(fo|ba|0)/' ) );
		$this->assertSame( [ 'foo bar 0' ], array_values( DOMUtils::attributes( $el ) ) );
		DOMUtils::$removeMethod( $el, '' ); # should be a no-op
		$this->assertSame( true, DOMUtils::$hasMethod( $el, 'foo' ) );
		$this->assertSame( true, DOMUtils::$hasMethod( $el, 'bar' ) );
		$this->assertSame( true, DOMUtils::$hasMethod( $el, '0' ) );
		$this->assertNotNull( DOMUtils::$matchMethod( $el, '/^(fo|ba|0)/' ) );
		$this->assertSame( [ 'foo bar 0' ], array_values( DOMUtils::attributes( $el ) ) );
		DOMUtils::$removeMethod( $el, ' ' ); # should be a no-op
		$this->assertSame( true, DOMUtils::$hasMethod( $el, 'foo' ) );
		$this->assertSame( true, DOMUtils::$hasMethod( $el, 'bar' ) );
		$this->assertSame( true, DOMUtils::$hasMethod( $el, '0' ) );
		$this->assertNotNull( DOMUtils::$matchMethod( $el, '/^(fo|ba|0)/' ) );
		DOMUtils::$removeMethod( $el, 'foo' );
		$this->assertSame( false, DOMUtils::$hasMethod( $el, 'foo' ) );
		$this->assertSame( true, DOMUtils::$hasMethod( $el, 'bar' ) );
		$this->assertSame( true, DOMUtils::$hasMethod( $el, '0' ) );
		$this->assertSame( 'bar', DOMUtils::$matchMethod( $el, '/^(fo|ba)/' ) );
		DOMUtils::$removeMethod( $el, 'bar' );
		$this->assertSame( false, DOMUtils::$hasMethod( $el, 'foo' ) );
		$this->assertSame( false, DOMUtils::$hasMethod( $el, 'bar' ) );
		$this->assertSame( true, DOMUtils::$hasMethod( $el, '0' ) );
		$this->assertSame( '0', DOMUtils::$matchMethod( $el, '/^(fo|ba|0)/' ) );
		DOMUtils::$removeMethod( $el, '0' );
		$this->assertSame( false, DOMUtils::$hasMethod( $el, 'foo' ) );
		$this->assertSame( false, DOMUtils::$hasMethod( $el, 'bar' ) );
		$this->assertSame( false, DOMUtils::$hasMethod( $el, '0' ) );
		$this->assertSame( null, DOMUtils::$matchMethod( $el, '/^(fo|ba|0)/' ) );
		$this->assertSame( [], DOMUtils::attributes( $el ) );
	}

	public function provideMultivalAttr() {
		return [ [ 'TypeOf' ], [ 'Rel' ] ];
	}

	/**
	 * @covers ::addTypeOf
	 */
	public function testAddTypeOfPrepend() {
		$doc = DOMUtils::parseHtml( '<span>' );
		$el = DOMCompat::querySelector( $doc, 'span' );
		$this->assertSame( [], DOMUtils::attributes( $el ) );
		DOMUtils::addTypeOf( $el, 'foo' );
		$this->assertSame( [ 'typeof' => 'foo' ], DOMUtils::attributes( $el ) );
		DOMUtils::addTypeOf( $el, 'bar' );
		$this->assertSame( [ 'typeof' => 'foo bar' ], DOMUtils::attributes( $el ) );
		DOMUtils::addTypeOf( $el, 'bat', true /* prepend */ );
		$this->assertSame( [ 'typeof' => 'bat foo bar' ], DOMUtils::attributes( $el ) );
		# these should all be no-ops
		DOMUtils::addTypeOf( $el, '' );
		$this->assertSame( [ 'typeof' => 'bat foo bar' ], DOMUtils::attributes( $el ) );
		DOMUtils::addTypeOf( $el, " \t " );
		$this->assertSame( [ 'typeof' => 'bat foo bar' ], DOMUtils::attributes( $el ) );
		DOMUtils::addTypeOf( $el, '', true );
		$this->assertSame( [ 'typeof' => 'bat foo bar' ], DOMUtils::attributes( $el ) );
		DOMUtils::addTypeOf( $el, " \t ", true );
		$this->assertSame( [ 'typeof' => 'bat foo bar' ], DOMUtils::attributes( $el ) );
	}

	/**
	 * @covers ::hasClass
	 */
	public function testHasClass() {
		$doc = DOMUtils::parseHtml( '<span class="0">' );
		$el = DOMCompat::querySelector( $doc, 'span' );
		$this->assertTrue( DOMUtils::hasClass( $el, '\\d' ) );
	}

}

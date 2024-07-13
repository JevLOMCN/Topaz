<?php

namespace Test\Parsoid\Utils;

use Wikimedia\Parsoid\DOM\Document;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMCompat\TokenList;
use Wikimedia\Parsoid\Utils\DOMUtils;
use Wikimedia\Parsoid\Wt2Html\XMLSerializer;
use Wikimedia\TestingAccessWrapper;

/**
 * @coversDefaultClass \Wikimedia\Parsoid\Utils\DOMCompat
 */
class DOMCompatTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @covers ::getBody()
	 */
	public function testGetBody() {
		$html = '<html><head><title>Foo</title></head><body id="x"><div>y</div></body></html>';
		$doc = DOMCompat::newDocument( true );
		$doc->loadHTML( $html );
		$body = DOMCompat::getBody( $doc );
		$this->assertSameNode( $body, $doc->getElementById( 'x' ) );

		$html = '<html><head><title>Foo</title></head></html>';
		$doc = DOMCompat::newDocument( true );
		$doc->loadHTML( $html, LIBXML_HTML_NOIMPLIED );
		$body = DOMCompat::getBody( $doc );
		$this->assertNull( $body );
	}

	/**
	 * @covers ::getHead()
	 */
	public function testGetHead() {
		$html = '<html><head id="x"><title>Foo</title></head><body><div>y</div></body></html>';
		$doc = DOMCompat::newDocument( true );
		$doc->loadHTML( $html );
		$head = DOMCompat::getHead( $doc );
		$this->assertSameNode( $head, $doc->getElementById( 'x' ) );

		$html = '<html><body><div>y</div></body></html>';
		$doc = DOMCompat::newDocument( true );
		$doc->loadHTML( $html, LIBXML_HTML_NOIMPLIED );
		$head = DOMCompat::getHead( $doc );
		$this->assertNull( $head );
	}

	/**
	 * @covers ::getTitle()
	 */
	public function testGetTitle() {
		$html = '<html><head><title>Foo</title></head><body><div>y</div></body></html>';
		$doc = DOMCompat::newDocument( true );
		$doc->loadHTML( $html );
		$title = DOMCompat::getTitle( $doc );
		$this->assertSame( 'Foo', $title );

		$html = '<html><head><title> Foo&#9;Bar  </title></head><body><div>y</div></body></html>';
		$doc = DOMCompat::newDocument( true );
		$doc->loadHTML( $html );
		$title = DOMCompat::getTitle( $doc );
		$this->assertSame( 'Foo Bar', $title );

		$html = '<html><body><div>y</div></body></html>';
		$doc = DOMCompat::newDocument( true );
		$doc->loadHTML( $html, LIBXML_HTML_NOIMPLIED );
		$title = DOMCompat::getTitle( $doc );
		$this->assertSame( '', $title );
	}

	/**
	 * @covers ::setTitle()
	 */
	public function testSetTitle() {
		// modify <title> if it is exist
		$html = '<html><head><title>Foo</title></head><body><div>y</div></body></html>';
		$doc = DOMCompat::newDocument( true );
		$doc->loadHTML( $html );
		DOMCompat::setTitle( $doc, 'Bar' );
		$title = $doc->getElementsByTagName( 'title' )->item( 0 );
		$this->assertInstanceOf( Element::class, $title );
		$this->assertSame( 'Bar', $title->textContent );

		// ...even if it is not in <head>
		$html = '<html><body><title>Foo</title><div>y</div></body></html>';
		$doc = DOMCompat::newDocument( true );
		$doc->loadHTML( $html, LIBXML_HTML_NOIMPLIED );
		DOMCompat::setTitle( $doc, 'Bar' );
		$title = $doc->getElementsByTagName( 'title' )->item( 0 );
		$this->assertInstanceOf( Element::class, $title );
		$this->assertSame( 'Bar', $title->textContent );

		// append it to <head> if it does not exist
		$html = '<html><head><style></style></head><body><div>y</div></body></html>';
		$doc = DOMCompat::newDocument( true );
		$doc->loadHTML( $html, LIBXML_HTML_NOIMPLIED );
		DOMCompat::setTitle( $doc, 'Bar' );
		$title = $doc->getElementsByTagName( 'title' )->item( 0 );
		$this->assertInstanceOf( Element::class, $title );
		$this->assertSame( 'Bar', $title->textContent );
		$this->assertNotSame( $title, $title->parentNode->firstChild );
		$this->assertSame( $title, $title->parentNode->lastChild );

		// bail out if there's no <head>
		$html = '<html><body><div>y</div></body></html>';
		$doc = DOMCompat::newDocument( true );
		$doc->loadHTML( $html, LIBXML_HTML_NOIMPLIED );
		DOMCompat::setTitle( $doc, 'Bar' );
		$head = $doc->getElementsByTagName( 'head' )->item( 0 );
		$this->assertNull( $head );
		$title = $doc->getElementsByTagName( 'title' )->item( 0 );
		$this->assertNull( $title );
	}

	/**
	 * @covers ::getParentElement()
	 */
	public function testGetParentElement() {
		// has parent element
		$html = '<html><body id="x"><div>1</div><div id="y">2</div></body></html>';
		$doc = DOMCompat::newDocument( true );
		$doc->loadHTML( $html );
		$element = $doc->getElementById( 'y' );
		$this->assertSame( $doc->getElementById( 'x' ), DOMCompat::getParentElement( $element ) );

		// no parent element
		$html = '<html></html>';
		$doc = DOMCompat::newDocument( true );
		$doc->loadHTML( $html );
		$this->assertNull( DOMCompat::getParentElement( $doc->documentElement ) );

		// TODO is it possible to have a node with non-Element parent element?
	}

	/**
	 * @covers ::getElementById()
	 */
	public function testGetElementById() {
		$html = '<html><body><div id="x"></div></body></html>';
		$doc = DOMCompat::newDocument( true );
		$doc->loadHTML( $html );

		$x = $doc->getElementById( 'x' );
		$this->assertSame( $x, DOMCompat::getElementById( $doc, 'x' ) );

		$x->parentNode->removeChild( $x );

		// https://bugs.php.net/bug.php?id=77686
		if ( version_compare( PHP_VERSION, '8.1.20', '>' ) ) {
			$this->assertNull( $doc->getElementById( 'x' ) );
		} else {
			$this->assertSame( $x, $doc->getElementById( 'x' ) );
			$this->assertNull( DOMCompat::getElementById( $doc, 'x' ) );
		}
	}

	/**
	 * @covers ::getLastElementChild()
	 */
	public function testGetLastElementChild() {
		$html = '<html><body><div id="a"></div>1<div id="b"></div><div id="c"></div>3</body></html>';
		$doc = DOMCompat::newDocument( true );
		$doc->loadHTML( $html );
		$html = $doc->getElementsByTagName( 'html' )->item( 0 );
		'@phan-var Element $html'; /** @var Element $html */
		$body = $doc->getElementsByTagName( 'body' )->item( 0 );
		'@phan-var Element $body'; /** @var Element $body */
		$this->assertSameNode( $doc->getElementById( 'c' ), DOMCompat::getLastElementChild( $body ) );
		$this->assertSameNode( $html, DOMCompat::getLastElementChild( $doc ) );
		$this->assertSameNode( $body, DOMCompat::getLastElementChild( $html ) );

		$html = '<html><body></body></html>';
		$doc = DOMCompat::newDocument( true );
		$doc->loadHTML( $html );
		$body = $doc->getElementsByTagName( 'body' )->item( 0 );
		'@phan-var Element $body'; /** @var Element $body */
		$this->assertNull( DOMCompat::getLastElementChild( $body ) );
	}

	/**
	 * @dataProvider provideQuerySelector
	 * @covers ::querySelector()
	 * @param string $html
	 * @param string $selector
	 * @param callback|null $contextCallback
	 * @param string|null $expectedDataIds
	 */
	public function testQuerySelector( $html, $selector, $contextCallback, $expectedDataIds ) {
		$doc = DOMCompat::newDocument( true );
		$doc->loadHTML( $html );
		$context = $contextCallback ? $contextCallback( $doc ) : $doc->documentElement;
		$result = DOMCompat::querySelector( $context, $selector );

		$expectedDataId = $expectedDataIds[0] ?? null;
		$actualDataId = $result ? DOMCompat::getAttribute( $result, 'data-id' ) : null;
		$this->assertSame( $expectedDataId, $actualDataId );
	}

	/**
	 * @dataProvider provideQuerySelector
	 * @covers ::querySelectorAll()
	 * @param string $html
	 * @param string $selector
	 * @param callback|null $contextCallback
	 * @param string|null $expectedDataIds
	 */
	public function testQuerySelectorAll( $html, $selector, $contextCallback, $expectedDataIds ) {
		$doc = DOMCompat::newDocument( true );
		$doc->loadHTML( $html );
		$context = $contextCallback ? $contextCallback( $doc ) : $doc->documentElement;
		$results = DOMCompat::querySelectorAll( $context, $selector );

		$actualDataIds = [];
		foreach ( $results as $result ) {
			$actualDataIds[] = DOMCompat::getAttribute( $result, 'data-id' );
		}
		$this->assertSame( $expectedDataIds, $actualDataIds );
	}

	public function provideQuerySelector() {
		// TODO would be nicer to have separate HTML per test (maybe something like parser tests)
		$html1 = <<<'HTML'
<html><body>
	<div id="a" class="a" data-id="a">
		<div class="b1 b2 b3" data-id="b">
			<div rel="c" data-id="c" rel2="x">1</div>
			<div data-id="empty-div"></div>
		</div>
	</div>'
	<span rel2="x" data-id="span"></span>
	<div class="e">
		<div class="xxx" data-id="x1"></div>
	</div>
	<div class="e" id="ctx1">
		<div>
			<div class="xxx" data-id="x2"></div>
		</div>
	</div>
	<div class="f" id="ctx2" data-id="f">
		<div class="f xxx" data-id="x3"></div>
	</div>
</body></html>
HTML;

		return [
			// simple selectors
			'id' => [
				'html' => $html1,
				'selector' => '#a',
				'context' => null,
				'expectedDataId' => [ 'a' ],
			],
			'simple class' => [
				'html' => $html1,
				'selector' => '.a',
				'context' => null,
				'expectedDataId' => [ 'a' ],
			],
			'compound class string, start' => [
				'html' => $html1,
				'selector' => '.b1',
				'context' => null,
				'expectedTagHtmls' => [ 'b' ],
			],
			'compound class string, mid' => [
				'html' => $html1,
				'selector' => '.b2',
				'context' => null,
				'expectedTagHtmls' => [ 'b' ],
			],
			'compound class string, end' => [
				'html' => $html1,
				'selector' => '.b3',
				'context' => null,
				'expectedTagHtmls' => [ 'b' ],
			],
			'attribute' => [
				'html' => $html1,
				'selector' => '[rel=c]',
				'context' => null,
				'expectedTagHtmls' => [ 'c' ],
			],
			'attribute #2' => [
				'html' => $html1,
				'selector' => '[rel2=x]',
				'context' => null,
				'expectedTagHtmls' => [ 'c', 'span' ],
			],
			'attribute, case sensitive' => [
				'html' => $html1,
				'selector' => '[rel=C]',
				'context' => null,
				'expectedTagHtmls' => [],
			],
			/* not yet supported in css-parser
			'attribute, case insensitive' => [
				'html' => $html1,
				'selector' => '[rel="C"i]',
				'context' => null,
				'expectedTagHtmls' => [],
			],
			*/
			'attribute word' => [
				'html' => $html1,
				'selector' => '[class~=b2]',
				'context' => null,
				'expectedTagHtmls' => [ 'b' ],
			],
			'attribute word, case sensitive' => [
				'html' => $html1,
				'selector' => '[class~=B2]',
				'context' => null,
				'expectedTagHtmls' => [],
			],
			/* not yet supported in css-parser
			'attribute word, case insensitive' => [
				'html' => $html1,
				'selector' => '[class~="B2"i]',
				'context' => null,
				'expectedTagHtmls' => [ 'b' ],
			],
			*/
			'tag' => [
				'html' => $html1,
				'selector' => 'span',
				'context' => null,
				'expectedTagHtmls' => [ 'span' ],
			],
			':empty' => [
				'html' => $html1,
				'selector' => ':empty',
				'context' => null,
				'expectedTagHtmls' => [ 'empty-div', 'span', 'x1', 'x2', 'x3' ],
			],
			'recursion' => [
				'html' => $html1,
				'selector' => '.f',
				'context' => null,
				'expectedTagHtmls' => [ 'f', 'x3' ],
			],

			// simple selector sequence
			'id + class' => [
				'html' => $html1,
				'selector' => '#a.a',
				'context' => null,
				'expectedTagHtmls' => [ 'a' ],
			],
			'id + class #2' => [
				'html' => $html1,
				'selector' => '.a#a',
				'context' => null,
				'expectedTagHtmls' => [ 'a' ],
			],
			'multiple classes' => [
				'html' => $html1,
				'selector' => '.b2.b3',
				'context' => null,
				'expectedTagHtmls' => [ 'b' ],
			],
			'element + attribute' => [
				'html' => $html1,
				'selector' => 'div[rel2=x]',
				'context' => null,
				'expectedTagHtmls' => [ 'c' ],
			],
			'star + attribute' => [
				'html' => $html1,
				'selector' => '*[rel=c]',
				'context' => null,
				'expectedTagHtmls' => [ 'c' ],
			],

			// selector
			'child' => [
				'html' => $html1,
				'selector' => '.e > .xxx',
				'context' => null,
				'expectedTagHtmls' => [ 'x1' ],
			],
			'descendant' => [
				'html' => $html1,
				'selector' => '.e .xxx',
				'context' => null,
				'expectedTagHtmls' => [ 'x1', 'x2' ],
			],

			'selector group' => [
				'html' => $html1,
				'selector' => '#a, .e .xxx',
				'context' => null,
				'expectedTagHtmls' => [ 'a', 'x1', 'x2' ],
			],

			// context
			'context check' => [
				'html' => $html1,
				'selector' => '.xxx',
				'context' => null,
				'expectedTagHtmls' => [ 'x1', 'x2', 'x3' ],
			],
			'does not select outside context' => [
				'html' => $html1,
				'selector' => '.xxx',
				'context' => static function ( Document $doc ) {
					return $doc->getElementById( 'ctx1' );
				},
				'expectedTagHtmls' => [ 'x2' ],
			],
			'does not select context' => [
				'html' => $html1,
				'selector' => '.f',
				'context' => static function ( Document $doc ) {
					return $doc->getElementById( 'ctx2' );
				},
				'expectedTagHtmls' => [ 'x3' ],
			],
		];
	}

	/**
	 * @covers ::getPreviousElementSibling()
	 */
	public function testGetPreviousElementSibling() {
		$html = '<html><body>0<div id="a"></div>1<div id="b"></div><div id="c"></div>3</body></html>';
		$doc = DOMCompat::newDocument( true );
		$doc->loadHTML( $html );
		$a = $doc->getElementById( 'a' );
		$b = $doc->getElementById( 'b' );
		$c = $doc->getElementById( 'c' );
		$this->assertNull( DOMCompat::getPreviousElementSibling( $a ) );
		$this->assertSameNode( $a, DOMCompat::getPreviousElementSibling( $b ) );
		$this->assertSameNode( $b, DOMCompat::getPreviousElementSibling( $c ) );
	}

	/**
	 * @covers ::getNextElementSibling()
	 */
	public function testGetNextElementSibling() {
		$html = '<html><body>0<div id="a"></div>1<div id="b"></div><div id="c"></div>3</body></html>';
		$doc = DOMCompat::newDocument( true );
		$doc->loadHTML( $html );
		$a = $doc->getElementById( 'a' );
		$b = $doc->getElementById( 'b' );
		$c = $doc->getElementById( 'c' );
		$this->assertSameNode( $b, DOMCompat::getNextElementSibling( $a ) );
		$this->assertSameNode( $c, DOMCompat::getNextElementSibling( $b ) );
		$this->assertNull( DOMCompat::getNextElementSibling( $c ) );
	}

	/**
	 * @covers ::remove()
	 */
	public function testRemove() {
		$html = '<html><body><div id="a"></div><div id="b"><span id="c">1</span></div>2</body></html>';
		$doc = DOMCompat::newDocument( true );
		$doc->loadHTML( $html );

		// do not error when element has no parent
		$elmt = $doc->createElement( 'div' );
		DOMCompat::remove( $elmt );

		$b = $doc->getElementById( 'b' );
		$this->assertNotNull( $doc->getElementById( 'a' ) );
		$this->assertNotNull( $doc->getElementById( 'b' ) );
		$this->assertNotNull( $doc->getElementById( 'c' ) );
		DOMCompat::remove( $b );
		$this->assertNotNull( $doc->getElementById( 'a' ) );

		$this->assertSame( '<html><body><div id="a"></div>2</body></html>',
			DOMCompat::getOuterHTML( $doc->documentElement ) );

		// https://bugs.php.net/bug.php?id=77686
		if ( version_compare( PHP_VERSION, '8.1.20', '>' ) ) {
			$this->assertNull( $doc->getElementById( 'b' ) );
			$this->assertNull( $doc->getElementById( 'c' ) );
		}
	}

	/**
	 * @covers ::getInnerHTML()
	 */
	public function testGetInnerHTML() {
		$html = '<html><body>0<div id="a"></div>1<div id="b">2</div><div id="c"></div>3</body></html>';
		$doc = DOMCompat::newDocument( true );
		$doc->loadHTML( $html );
		$body = $doc->getElementsByTagName( 'body' )->item( 0 );
		'@phan-var Element $body'; /** @var Element $body */
		$b = $doc->getElementById( 'b' );
		$this->assertSame( '<body>0<div id="a"></div>1<div id="b">2</div><div id="c"></div>3</body>',
			DOMCompat::getInnerHTML( $doc->documentElement ) );
		$this->assertSame( '0<div id="a"></div>1<div id="b">2</div><div id="c"></div>3',
			DOMCompat::getInnerHTML( $body ) );
		$this->assertSame( '2', DOMCompat::getInnerHTML( $b ) );
	}

	/**
	 * @covers ::setInnerHTML()
	 */
	public function testSetInnerHTML() {
		$html = '<html><body><div>1</div><div>2</div>3</body></html>';
		$innerHtml = '<div>4</div><!-- 5 -->6';
		$doc = DOMCompat::newDocument( true );
		$doc->loadHTML( $html );
		$body = $doc->getElementsByTagName( 'body' )->item( 0 );
		'@phan-var Element $body'; /** @var Element $body */
		DOMCompat::setInnerHTML( $body, $innerHtml );
		$this->assertSame( "<body>$innerHtml</body>", $doc->saveXML( $body, LIBXML_NSCLEAN ) );
		$this->assertSame( '4',
			$doc->getElementsByTagName( 'body' )->item( 0 )->firstChild->textContent );
	}

	/**
	 * @covers ::getOuterHTML()
	 */
	public function testGetOuterHTML() {
		$html = '<html><body>0<div id="a"></div>1<div id="b">2</div><div id="c"></div>3</body></html>';
		$doc = DOMCompat::newDocument( true );
		$doc->loadHTML( $html );
		$body = $doc->getElementsByTagName( 'body' )->item( 0 );
		'@phan-var Element $body'; /** @var Element $body */
		$b = $doc->getElementById( 'b' );
		$this->assertSame(
			'<html><body>0<div id="a"></div>1<div id="b">2</div><div id="c"></div>3</body></html>',
			DOMCompat::getOuterHTML( $doc->documentElement ) );
		$this->assertSame( '<body>0<div id="a"></div>1<div id="b">2</div><div id="c"></div>3</body>',
			DOMCompat::getOuterHTML( $body ) );
		$this->assertSame( '<div id="b">2</div>', DOMCompat::getOuterHTML( $b ) );
	}

	/**
	 * @covers ::getClassList()
	 * @covers \Wikimedia\Parsoid\Utils\DOMCompat\TokenList
	 */
	public function testGetClassList() {
		$html = '<html><body><div id="x" class=" a  b&#9;c "></div></body></html>';
		$doc = DOMCompat::newDocument( true );
		$doc->loadHTML( $html );
		$x = $doc->getElementById( 'x' );
		$classList = DOMCompat::getClassList( $x );
		$this->assertInstanceOf( TokenList::class, $classList );
		$this->assertCount( 3, $classList );
		$this->assertTrue( $classList->contains( 'a' ) );
		$this->assertTrue( $classList->contains( 'b' ) );
		$this->assertTrue( $classList->contains( 'c' ) );
		$this->assertFalse( $classList->contains( 'd' ) );
		$this->assertSame( [ 'a', 'b', 'c' ], iterator_to_array( $classList ) );
		// make sure rewinding works
		$this->assertSame( [ 'a', 'b', 'c' ], iterator_to_array( $classList ) );
		$classList->add( 'd' );
		$classList->remove( 'b' );
		$this->assertFalse( $classList->contains( 'b' ) );
		$this->assertTrue( $classList->contains( 'd' ) );
		$this->assertSame( [ 'a', 'c', 'd' ], iterator_to_array( $classList ) );
		$this->assertSame( '<div id="x" class="a c d"></div>', DOMCompat::getOuterHTML( $x ) );
		$x->setAttribute( 'class', 'e f g' );
		$this->assertSame( [ 'e', 'f', 'g' ], iterator_to_array( $classList ) );
		$classList->add( 'a' );
		$classList->remove( 'g' );
		$this->assertSame( 'e f a', DOMCompat::getAttribute( $x, 'class' ) );
	}

	/**
	 * @covers ::stripAndCollapseASCIIWhitespace()
	 * @dataProvider provideStripAndCollapseASCIIWhitespace
	 */
	public function testStripAndCollapseASCIIWhitespace( $input, $expectedOutput ) {
		$domCompat = TestingAccessWrapper::newFromClass( DOMCompat::class );
		$actualOutput = $domCompat->stripAndCollapseASCIIWhitespace( $input );
		$this->assertSame( $expectedOutput, $actualOutput );
	}

	public function provideStripAndCollapseASCIIWhitespace() {
		return [
			[ '  foo  ', 'foo' ],
			[ " \n foo \t \n bar ", 'foo bar' ],
			[ "foo\r\fbar", 'foo bar' ],
			[ " \n ", '' ],
		];
	}

	private function assertSameNode( Node $expected, Node $actual, $message = '' ) {
		if ( !$expected->isSameNode( $actual ) ) {
			// try to give a somewhat informative error
			$actualHtml = XMLSerializer::serialize( $actual )['html'];
			$expectedHtml = XMLSerializer::serialize( $expected )['html'];
			$this->assertSame( $expectedHtml, $actualHtml, $message );
			$this->assertSame( $expected, $actual, $message );
		} else {
			$this->assertTrue( true );
		}
	}

	/**
	 * See https://bugs.php.net/bug.php?id=78221 for the upstream bug
	 * we're working around here.
	 * @covers ::normalize()
	 * @dataProvider provideNormalize
	 */
	public function testNormalize( $textNodeCount, $interleaveSpan, $expectedNodeCount ) {
		$doc = DOMCompat::newDocument( true );
		$doc->loadXML( "<html><body></body></html>" );
		$body = $doc->getElementsByTagName( 'body' )->item( 0 );
		$div = $doc->createElement( 'div' );
		$body->appendChild( $div );
		while ( $textNodeCount > 0 ) {
			$div->appendChild( $doc->createTextNode( '' ) );
			if ( $interleaveSpan ) {
				$div->appendChild( $doc->createElement( 'span' ) );
			}
			$textNodeCount--;
		}

		DOMCompat::normalize( $body );
		$this->assertSame( $expectedNodeCount, $div->childNodes->length );
	}

	public function provideNormalize() {
		return [
			[
				"textNodeCount" => 1,
				"interleaveSpan" => false,
				"expected" => 0
			],
			[
				"textNodeCount" => 5,
				"interleaveSpan" => false,
				"expected" => 0
			],
			[
				"textNodeCount" => 1,
				"interleaveSpan" => true,
				"expected" => 1
			],
			[
				"textNodeCount" => 5,
				"interleaveSpan" => true,
				"expected" => 5
			],
		];
	}

	/**
	 * @covers ::setIdAttribute
	 */
	public function testSetIdAttribute() {
		$doc = DOMUtils::parseHTML( "<p class=xyz>Hello, world</p>" );
		$head = DOMCompat::getHead( $doc );
		$elmt = $doc->createElement( 'div' );
		$head->appendChild( $elmt );
		DOMCompat::setIdAttribute( $elmt, "this-is-an-id" );

		// Note we're testing the "fast path" native implementation here,
		// not the workaround version in DOMCompat::getElementById()
		$q = $doc->getElementById( 'this-is-an-id' );
		$this->assertNotEquals( null, $q );
		$this->assertEquals( 'this-is-an-id', DOMCompat::getAttribute( $q, 'id' ) );
	}
}

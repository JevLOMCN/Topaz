<?php

namespace Test\Parsoid\Utils;

use Wikimedia\Bcp47Code\Bcp47CodeValue;
use Wikimedia\Parsoid\NodeData\I18nInfo;
use Wikimedia\Parsoid\Utils\ContentUtils;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMDataUtils;
use Wikimedia\Parsoid\Utils\DOMUtils;
use Wikimedia\Parsoid\Utils\WTUtils;

/**
 * @coversDefaultClass \Wikimedia\Parsoid\Utils\WTUtils
 */
class WTUtilsTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @covers ::encodeComment
	 * @covers ::decodeComment
	 * @covers ::decodedCommentLength
	 * @dataProvider provideCommentEncoding
	 */
	public function testCommentEncoding( string $wikitext, string $html, int $length ) {
		$actualHtml = WTUtils::encodeComment( $wikitext );
		$this->assertEquals( $html, $actualHtml );
		$actualWt = WTUtils::decodeComment( $html );
		$this->assertEquals( $wikitext, $actualWt );
		$doc = DOMCompat::newDocument( true );
		$doc->loadHTML( "<html><body><!--$html--></body></html>" );
		$body = $doc->getElementsByTagName( "body" )->item( 0 );
		$node = $body->childNodes->item( 0 );
		$actualLen = WTUtils::decodedCommentLength( $node );
		$this->assertEquals( $length, $actualLen );
	}

	public function provideCommentEncoding(): array {
		// length includes the length of the <!-- and --> delimiters
		return [
			[ 'abc', 'abc', 10 ],
			[ '& - >', '&#x26; &#x2D; &#x3E;', 12 ],
			[ 'Use &gt; here', 'Use &#x26;gt; here', 20 ],
			[ '--&gt;', '&#x2D;&#x2D;&#x3E;', 13 ],
			[ '--&amp;gt;', '&#x2D;&#x2D;&#x26;gt;', 17 ],
			[ '--&amp;amp;gt;', '&#x2D;&#x2D;&#x26;amp;gt;', 21 ],
		];
	}

	/**
	 * @covers ::createPageContentI18nFragment
	 */
	public function testCreatePageContentI18nFragment() {
		$doc = ContentUtils::createAndLoadDocument( "<html><body></body></html>" );
		$fragment = WTUtils::createPageContentI18nFragment( $doc, 'key.of.message' );
		DOMDataUtils::visitAndStoreDataAttribs( $fragment, [ 'discardDataParsoid' => true ] );
		$actualHtml = DOMUtils::getFragmentInnerHTML( $fragment );
		$expectedHtml = '<span typeof="mw:I18n" ' .
			'data-mw-i18n=\'{"/":{"lang":"x-page","key":"key.of.message","params":null}}\'></span>';
		self::assertEquals( $expectedHtml, $actualHtml );
	}

	/**
	 * @covers ::createInterfaceI18nFragment
	 */
	public function testCreateInterfaceI18nFragment() {
		$doc = ContentUtils::createAndLoadDocument( "<html><body></body></html>" );
		$fragment = WTUtils::createInterfaceI18nFragment( $doc, 'key.of.message', [ 'Foo' ] );
		DOMDataUtils::visitAndStoreDataAttribs( $fragment, [ 'discardDataParsoid' => true ] );
		$actualHtml = DOMUtils::getFragmentInnerHTML( $fragment );
		$expectedHtml = '<span typeof="mw:I18n" ' .
			'data-mw-i18n=\'{"/":{"lang":"x-user","key":"key.of.message","params":["Foo"]}}\'></span>';
		self::assertEquals( $expectedHtml, $actualHtml );
	}

	/**
	 * @covers ::createLangI18nFragment
	 */
	public function testCreateLangI18nFragment() {
		$doc = ContentUtils::createAndLoadDocument( "<html><body></body></html>" );
		$lang = new Bcp47CodeValue( 'fr' );
		$fragment = WTUtils::createLangI18nFragment( $doc, $lang, 'key.of.message' );
		DOMDataUtils::visitAndStoreDataAttribs( $fragment, [ 'discardDataParsoid' => true ] );
		$actualHtml = DOMUtils::getFragmentInnerHTML( $fragment );
		$expectedHtml = '<span typeof="mw:I18n" ' .
			'data-mw-i18n=\'{"/":{"lang":"fr","key":"key.of.message","params":null}}\'></span>';
		self::assertEquals( $expectedHtml, $actualHtml );
	}

	/**
	 * @covers ::addInterfaceI18nAttribute
	 * @covers ::addPageContentI18nAttribute
	 * @covers ::addLangI18nAttribute
	 */
	public function testAddI18nAttributes() {
		$doc = ContentUtils::createAndLoadDocument( "<html><body><span>hello</span></body></html>" );
		$span = DOMCompat::getBody( $doc )->firstChild;
		WTUtils::addPageContentI18nAttribute( $span, 'param1', 'key1' );
		WTUtils::addInterfaceI18nAttribute( $span, 'param2', 'key2', [ 'Foo' ] );
		$lang = new Bcp47CodeValue( 'fr' );
		WTUtils::addLangI18nAttribute( $span, $lang, 'param3', 'key3' );
		DOMDataUtils::visitAndStoreDataAttribs( $doc, [ 'discardDataParsoid' => true ] );
		$actualHtml = DOMCompat::getInnerHTML( DOMCompat::getBody( $doc ) );
		$expectedHtml = '<span typeof="mw:LocalizedAttrs" ' .
			'data-mw-i18n=\'{"param1":{"lang":"x-page","key":"key1","params":null},' .
			'"param2":{"lang":"x-user","key":"key2","params":["Foo"]},' .
			'"param3":{"lang":"fr","key":"key3","params":null}}\'>hello</span>';
		self::assertEquals( $expectedHtml, $actualHtml );
	}

	/**
	 * This also tests the storage/loading of mw-data-i18n attributes in the databag.
	 * @covers ::addPageContentI18nAttribute
	 * @covers ::createInterfaceI18nFragment
	 */
	public function testCombinedI18n() {
		$doc = ContentUtils::createAndLoadDocument( "<html><body></body></html>" );
		$fragment = WTUtils::createInterfaceI18nFragment( $doc, 'key.of.message', [ 'Foo' ] );
		WTUtils::addPageContentI18nAttribute( $fragment->firstChild, 'attr1', 'key1' );
		DOMDataUtils::visitAndStoreDataAttribs( $fragment, [ 'discardDataParsoid' => true ] );

		$newDoc = ContentUtils::createAndLoadDocument(
			'<html><body>' . DOMUtils::getFragmentInnerHTML( $fragment ) . '</body></html>' );
		$span = DOMCompat::getBody( $newDoc )->firstChild;
		$typeof = DOMUtils::attributes( $span )['typeof'];
		self::assertEquals( 'mw:I18n mw:LocalizedAttrs', $typeof );
		$spanI18n = DOMDataUtils::getDataNodeI18n( $span );
		self::assertNotNull( $spanI18n );
		$attrI18n = DOMDataUtils::getDataAttrI18n( $span, 'attr1' );
		self::assertNotNull( $attrI18n );
		self::assertEquals( I18nInfo::USER_LANG, $spanI18n->lang );
		self::assertEquals( [ 'Foo' ], $spanI18n->params );
		self::assertEquals( 'key.of.message', $spanI18n->key );
		self::assertEquals( I18nInfo::PAGE_LANG, $attrI18n->lang );
		self::assertNull( $attrI18n->params );
		self::assertEquals( 'key1', $attrI18n->key );
	}

	/**
	 * @covers ::decodedCommentLength
	 */
	public function testDecodedCommentLength() {
		$doc = DOMCompat::newDocument( true );
		$doc->loadHTML( "<html><body><div>" .
			"<p><!--c1--></p>" .
			"a <meta typeof='mw:Placeholder/UnclosedComment'/><!--c2\n-->" .
			"</body></html>" );
		$body = DOMCompat::getBody( $doc );
		$body->setAttribute( 'hasUnclosedComment', "1" );
		$div = $body->firstChild;
		$p = $div->firstChild;
		self::assertEquals( 7, WTUtils::decodedCommentLength( $div->lastChild ) );
		self::assertEquals( 9, WTUtils::decodedCommentLength( $p->lastChild ) );
	}
}

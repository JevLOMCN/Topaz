<?php
// phpcs:disable Generic.Files.LineLength.TooLong
declare( strict_types = 1 );

namespace Test\Parsoid;

use PHPUnit\Framework\TestCase;
use Wikimedia\Parsoid\Core\SelserData;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\Mocks\MockDataAccess;
use Wikimedia\Parsoid\Mocks\MockPageConfig;
use Wikimedia\Parsoid\Mocks\MockPageContent;
use Wikimedia\Parsoid\Mocks\MockSiteConfig;
use Wikimedia\Parsoid\Parsoid;
use Wikimedia\Parsoid\Utils\DiffDOMUtils;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMUtils;

/**
 * Tests parsing for regressions against specific issues that have been resolved
 * based on tests/mocha/regression.specs.js
 */
class RegressionSpecsTest extends TestCase {
	/**
	 * @param string $wt
	 * @param array $pageOpts
	 * @param bool $wrapSections
	 * @return Element
	 */
	private function parseWT( string $wt, array $pageOpts = [], $wrapSections = false ): Element {
		$siteConfig = new MockSiteConfig( [] );
		$dataAccess = new MockDataAccess( $siteConfig, [] );
		$parsoid = new Parsoid( $siteConfig, $dataAccess );

		$content = new MockPageContent( [ 'main' => $wt ] );
		$pageConfig = new MockPageConfig( $siteConfig, $pageOpts, $content );
		$html = $parsoid->wikitext2html( $pageConfig, [ "wrapSections" => $wrapSections ] );

		$doc = DOMUtils::parseHTML( $html );

		$docBody = DOMCompat::getBody( $doc );

		return( $docBody );
	}

	private function sharedTest(
		string $description, string $wt, array $search, array $replace,
		string $withoutSelser, string $withSelser
	): void {
		$siteConfig = new MockSiteConfig( [] );
		$dataAccess = new MockDataAccess( $siteConfig, [] );
		$parsoid = new Parsoid( $siteConfig, $dataAccess );

		$content = new MockPageContent( [ 'main' => $wt ] );
		$pageConfig = new MockPageConfig( $siteConfig, [], $content );
		$html = $parsoid->wikitext2html( $pageConfig, [ "wrapSections" => false ] );

		// This is mimicking a copy/paste in an editor
		$editedHTML = str_replace( $search, $replace, $html );

		// Without selser
		$editedWT = $parsoid->html2wikitext( $pageConfig, $editedHTML, [], null );
		$this->assertEquals( $withoutSelser, $editedWT, $description );

		// With selser
		$selserData = new SelserData( $wt, $html );
		$editedWT = $parsoid->html2wikitext( $pageConfig, $editedHTML, [], $selserData );
		$this->assertEquals( $withSelser, $editedWT, $description );
	}

	/**
	 * should prevent regression of T153107
	 * @covers \Wikimedia\Parsoid\Wt2Html\ParserPipeline
	 */
	public function testPreventRegressionT153107(): void {
		$description = "Regression Specs: should prevent regression of T153107";
		$wt = "[[Foo|bar]]";
		$searchItems = [ 'bar' ];
		$replaceItems = [ 'Foo' ];
		$withoutSelser = "[[Foo|Foo]]";
		$withSelser = "[[Foo]]";

		$this->sharedTest( $description, $wt, $searchItems, $replaceItems,
			$withoutSelser, $withSelser );
	}

	/**
	 * should prevent regression of T268737
	 * @covers \Wikimedia\Parsoid\Html2Wt\Separators::recoverTrimmedWhitespace
	 */
	public function testPreventRegressionT268737(): void {
		$description = "Regression Specs: should prevent regression of T262737";
		$wt = "* [[Foo]]--[[Bar]]";
		$searchItems = [ '--' ];
		$replaceItems = [ '..' ];
		$withoutSelser = "*[[Foo]]..[[Bar]]";
		$withSelser = "* [[Foo]]..[[Bar]]";

		$this->sharedTest( $description, $wt, $searchItems, $replaceItems,
			$withoutSelser, $withSelser );
	}

	/**
	 * should ensure edited lists, headings, table cells preserve original
	 * whitespace in some scenarios
	 * @covers \Wikimedia\Parsoid\Wt2Html\ParserPipeline
	 */
	public function testPreserveWhitespace(): void {
		$description = "should ensure edited lists, headings, table cells preserve original " .
			"whitespace in some scenarios";

		$wt = implode( "\n", [
			"* item",
			"* <!--cmt--> item",
			"* <div>item</div>",
			"* [[Link|item]]",
			"* wrap",
			"== heading ==",
			"== <!--cmt--> heading ==",
			"== <div>heading</div> ==",
			"== [[Link|heading]] ==",
			"{|",
			"| cell",
			"| <!--cmt--> cell",
			"| <div>cell</div>",
			"| [[Link|cell]]",
			"|  unedited c1  || cell ||  unedited c3  || cell",
			"|  unedited c1  || cell ||  unedited c3  ||   unedited c4",
			"|}"
		] );

		$searchItems = [ 'item', 'heading', 'cell', 'wrap' ];
		$replaceItems = [ 'edited item', 'edited heading', 'edited cell', '<span>wrap</span>' ];

		// Without selser, we should see normalized wikitext
		$withoutSelser = implode( "\n", [
			"*edited item",
			"*<!--cmt-->edited item",
			"*<div>edited item</div>",
			"*[[Link|edited item]]",
			"*<span>wrap</span>",
			"==edited heading==",
			"==<!--cmt-->edited heading==",
			"==<div>edited heading</div>==",
			"==[[Link|edited heading]]==",
			"{|",
			"|edited cell",
			"|<!--cmt-->edited cell",
			"|<div>edited cell</div>",
			"|[[Link|edited cell]]",
			"|unedited c1||edited cell||unedited c3||edited cell",
			"|unedited c1||edited cell||unedited c3||unedited c4",
			"|}"
		] );

		// With selser, we should have whitespace heuristics applied
		$withSelser = implode( "\n", [
			"* edited item",
			"* <!--cmt-->edited item",
			"* <div>edited item</div>",
			"* [[Link|edited item]]",
			"* <span>wrap</span>",
			"== edited heading ==",
			"== <!--cmt-->edited heading ==",
			"== <div>edited heading</div> ==",
			"== [[Link|edited heading]] ==",
			"{|",
			"| edited cell",
			"| <!--cmt-->edited cell",
			"| <div>edited cell</div>",
			"| [[Link|edited cell]]",
			"|  unedited c1  || edited cell ||  unedited c3  || edited cell",
			"|  unedited c1  || edited cell ||  unedited c3  ||   unedited c4",
			"|}"
		] );

		$this->sharedTest( $description, $wt, $searchItems, $replaceItems,
			$withoutSelser, $withSelser );
	}

	/**
	 * should not apply whitespace heuristics for HTML versions older than 1.7.0
	 * @covers \Wikimedia\Parsoid\Wt2Html\ParserPipeline
	 */
	public function testNoWhitespaceHeuristics(): void {
		$description = "should not apply whitespace heuristics for HTML versions older than 1.7.0";
		$wt = implode( "\n", [
			"* item",
			"* <!--cmt--> item",
			"* <div>item</div>",
			"* [[Link|item]]",
			"* wrap",
			"== heading ==",
			"== <!--cmt--> heading ==",
			"== <div>heading</div> ==",
			"== [[Link|heading]] ==",
			"{|",
			"| cell",
			"| <!--cmt--> cell",
			"| <div>cell</div>",
			"| [[Link|cell]]",
			"|}"
		] );

		$siteConfig = new MockSiteConfig( [] );
		$dataAccess = new MockDataAccess( $siteConfig, [] );
		$parsoid = new Parsoid( $siteConfig, $dataAccess );

		$content = new MockPageContent( [ 'main' => $wt ] );
		$pageConfig = new MockPageConfig( $siteConfig, [], $content );

		$html = $parsoid->wikitext2html( $pageConfig, [ "wrapSections" => false ] );

		$search = [ 'item', 'heading', 'cell', 'wrap' ];
		$replace = [ 'edited item', 'edited heading', 'edited cell', '<span>wrap</span>' ];
		$editedBody = str_replace( $search, $replace, $html );

		// Whitespace heuristics are enabled
		$newVersion = implode( "\n", [
			"* edited item",
			"* <!--cmt-->edited item",
			"* <div>edited item</div>",
			"* [[Link|edited item]]",
			"* <span>wrap</span>",
			"== edited heading ==",
			"== <!--cmt-->edited heading ==",
			"== <div>edited heading</div> ==",
			"== [[Link|edited heading]] ==",
			"{|",
			"| edited cell",
			"| <!--cmt-->edited cell",
			"| <div>edited cell</div>",
			"| [[Link|edited cell]]",
			"|}"
		] );

		$selserData = new SelserData( $wt, $html );
		$editedWT = $parsoid->html2wikitext( $pageConfig, $editedBody, [], $selserData );
		$this->assertEquals( $newVersion, $editedWT, $description );

		// Whitespace heuristics are disabled, but selser's buildSep heuristics
		// will do the magic for old non-text and non-comment nodes.
		$oldVersion = implode( "\n", [
			"*edited item",
			"*<!--cmt-->edited item",
			"* <div>edited item</div>",
			"* [[Link|edited item]]",
			"*<span>wrap</span>",
			"==edited heading==",
			"==<!--cmt-->edited heading==",
			"== <div>edited heading</div> ==",
			"== [[Link|edited heading]] ==",
			"{|",
			"|edited cell",
			"|<!--cmt-->edited cell",
			"| <div>edited cell</div>",
			"| [[Link|edited cell]]",
			"|}"
		] );

		// Pretend we are in 1.6.1 version to disable whitespace heuristics
		$htmlVersion = '1.6.1';

		$selserData = new SelserData( $wt, $html );
		$editedWT = $parsoid->html2wikitext( $pageConfig, $editedBody,
			[ 'inputContentVersion' => $htmlVersion ], $selserData );
		$this->assertEquals( $oldVersion, $editedWT, $description );
	}

	/**
	 * should not wrap templatestyles style tags in p-wrappers
	 * @covers \Wikimedia\Parsoid\Wt2Html\TT\TemplateHandler
	 */
	public function testNoWrapTemplateStyles(): void {
		$description = "Regression Specs: should not wrap templatestyles style tags in p-wrappers";
		$wt = "<templatestyles src='Template:Quote/styles.css'/><div>foo</div>";
		$docBody = $this->parseWT( $wt );
		$node = DOMCompat::nodeName( $docBody->firstChild );

		$this->assertEquals( "style", $node, $description );
	}

	/**
	 * For https://phabricator.wikimedia.org/T208901
	 * should not split p-wrappers around templatestyles
	 * @covers \Wikimedia\Parsoid\Wt2Html\TT\TemplateHandler
	 */
	public function testNoSplitPWrapper(): void {
		$description = "Regression Specs: should not split p-wrappers around templatestyles";
		$wt = 'abc {{1x|<templatestyles src="Template:Quote/styles.css" /> def}} ghi ' .
			'[[File:Thumb.png|thumb|250px]]';
		$docBody = $this->parseWT( $wt );

		$node = DOMCompat::nodeName( $docBody->firstChild );
		$this->assertEquals( "p", $node, $description );

		$node = DOMCompat::nodeName( DiffDOMUtils::nextNonSepSibling( $docBody->firstChild ) );
		$this->assertEquals( "figure", $node, $description );
	}

	/**
	 * Regression Specs: should deduplicate templatestyles style tags
	 * @covers \Wikimedia\Parsoid\Wt2Html\TT\TemplateHandler
	 */
	public function testNoDupTemplateStyleTags(): void {
		$description = "Regression Specs: should deduplicate templatestyles style tags";
		$wt = [
			'<templatestyles src="Template:Quote/styles.css" /><span>a</span>',
			'<templatestyles src="Template:Quote/styles.css" /><span>b</span>'
		];
		$wt = implode( "\n", $wt );
		$docBody = $this->parseWT( $wt );

		$firstStyle = $docBody->firstChild->firstChild;
		$this->assertEquals( "style", DOMCompat::nodeName( $firstStyle ), $description );

		$secondStyle = $firstStyle->nextSibling->nextSibling->nextSibling;
		$this->assertEquals( "link", DOMCompat::nodeName( $secondStyle ), $description );

		$this->assertEquals( "mw-deduplicated-inline-style",
			DOMCompat::getAttribute( $secondStyle, 'rel' ), $description );

		$this->assertEquals( 'mw-data:' .
			DOMCompat::getAttribute( $firstStyle, 'data-mw-deduplicate' ),
			DOMCompat::getAttribute( $secondStyle, 'href' ), $description );

		$keys = [ 'about', 'typeof', 'data-mw', 'data-parsoid' ];
		foreach ( $keys as $key ) {
			$this->assertTrue( $secondStyle->hasAttribute( $key ), $description );
		}
	}

	/**
	 * @covers \Wikimedia\Parsoid\Ext\Gallery\Gallery::domToWikitext
	 */
	public function testGalleryLineWithTemplatestyleInCaption(): void {
		$description = "Deduplicated templatestyles shouldn't lead to differing alt and caption text.";
		$wt = <<<EOT
The first instance ensures the one in the caption is deduped <templatestyles src="Template:Quote/styles.css" />
<gallery>
File:Foobar.jpg|caption with <templatestyles src="Template:Quote/styles.css" />
</gallery>
EOT;
		$docBody = $this->parseWT( $wt );

		$siteConfig = new MockSiteConfig( [] );
		$dataAccess = new MockDataAccess( $siteConfig, [] );
		$parsoid = new Parsoid( $siteConfig, $dataAccess );
		$content = new MockPageContent( [ 'main' => $wt ] );
		$pageConfig = new MockPageConfig( $siteConfig, [], $content );

		$editedWt = $parsoid->html2wikitext( $pageConfig, DOMCompat::getOuterHTML( $docBody ), [], null );

		$this->assertEquals( $wt, $editedWt );
	}

	/**
	 * @covers \Wikimedia\Parsoid\Config\SiteConfig::getMagicWordForMediaOption
	 */
	public function testSub(): void {
		$wt = <<<EOT
[[File:Foobar.jpg|sub|caption]]
EOT;
		$docBody = $this->parseWT( $wt );
		$figure = DOMCompat::querySelector( $docBody, 'span[typeof~="mw:File"]' );
		$this->assertInstanceOf( Element::class, $figure );
		$this->assertTrue( DOMUtils::hasClass( $figure, 'mw-valign-sub' ) );
	}

	/**
	 * Tests TOC edge cases in T350625 and T352467
	 * Cannot test some of this via parser tests because that framework strips
	 * about attributes and here, we want to assert the presence of 'about'.
	 * @covers \Wikimedia\Parsoid\Wt2Html\PP\Processors\WrapSectionsState
	 */
	public function testTocEdgeCases(): void {
		// For the test below,
		// - Synthetic meta should get an about id
		//   matching the surrounding template
		// - Synthetic section shoult not get an about id
		$wt = <<<EOT
<div>
{{1x|1=
foo
==h1==
}}
a
==h2==
b
==h3==
c
==h4==
d
</div>
EOT;
		$docBody = $this->parseWT( $wt, [], true );

		$syntheticMeta = DOMCompat::querySelector( $docBody, 'meta[property=mw:PageProp/toc]' );
		$about = DOMCompat::getAttribute( $syntheticMeta, 'about' );
		$this->assertNotNull( $about );

		$syntheticSection = $syntheticMeta->parentNode;
		$this->assertSame( '-2', DOMCompat::getAttribute( $syntheticSection, 'data-mw-section-id' ) );
		$this->assertSame( $about, DOMCompat::getAttribute( $syntheticSection->previousSibling, 'about' ) ); // <span> for \n after 'foo'
		$this->assertSame( $about, DOMCompat::getAttribute( $syntheticSection->nextSibling->firstChild, 'about' ) ); // <h1>
		$this->assertNull( DOMCompat::getAttribute( $syntheticSection, 'about' ) );

		// For the test below,
		// - Synthetic meta and synthetic section should not get an about id
		// - The synthetic section should be nested in a <div> tag
		$wt = <<<EOT
{{1x|foo
<div>}}
==h1==
a
==h2==
b
==h3==
c
==h4==
d
==h5==
e
EOT;
		$docBody = $this->parseWT( $wt, [], true );
		$syntheticMeta = DOMCompat::querySelector( $docBody, 'meta[property=mw:PageProp/toc]' );
		$this->assertNull( DOMCompat::getAttribute( $syntheticMeta, 'about' ) );

		$syntheticSection = $syntheticMeta->parentNode;
		$this->assertSame( '-2', DOMCompat::getAttribute( $syntheticSection, 'data-mw-section-id' ) );
		$this->assertSame( 'div', DOMCompat::nodeName( $syntheticSection->parentNode ) );
		$this->assertNull( DOMCompat::getAttribute( $syntheticSection, 'about' ) );
	}

}

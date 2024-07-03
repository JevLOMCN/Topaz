<?php

namespace Test\Parsoid\Wt2Html\PP\Handlers;

use PHPUnit\Framework\TestCase;
use Wikimedia\Parsoid\DOM\Document;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\Mocks\MockDataAccess;
use Wikimedia\Parsoid\Mocks\MockEnv;
use Wikimedia\Parsoid\Mocks\MockPageConfig;
use Wikimedia\Parsoid\Mocks\MockPageContent;
use Wikimedia\Parsoid\Mocks\MockSiteConfig;
use Wikimedia\Parsoid\Parsoid;
use Wikimedia\Parsoid\Utils\ContentUtils;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMDataUtils;
use Wikimedia\Parsoid\Utils\DOMTraverser;

/**
 * Test CleanUp, the tests used for validating CleanUp class port from JS to PHP
 * and based on tests/mocha/cleanup.js
 * @coversDefaultClass \Wikimedia\Parsoid\Wt2Html\PP\Handlers\CleanUp
 */
class CleanUpTest extends TestCase {

	/** @var Document[] */
	private $liveDocs = [];

	private function parseWT( string $wt ): Element {
		$siteConfig = new MockSiteConfig( [] );
		$dataAccess = new MockDataAccess( $siteConfig, [] );
		$parsoid = new Parsoid( $siteConfig, $dataAccess );

		$content = new MockPageContent( [ 'main' => $wt ] );
		$pageConfig = new MockPageConfig( $siteConfig, [], $content );
		$html = $parsoid->wikitext2html( $pageConfig, [ "wrapSections" => false ] );

		$doc = ContentUtils::createAndLoadDocument( $html );

		// Prevent GC from reclaiming $doc once we exit this function.
		// Necessary hack because we use PHPDOM which wraps libxml.
		$this->liveDocs[] = $doc;

		return DOMCompat::getBody( $doc );
	}

	private function addHandlers( DOMTraverser $domVisitor, array $tags, bool $value ): void {
		foreach ( $tags as $tag ) {
			$domVisitor->addHandler( $tag,
				function ( ...$args ) use ( $value ) {
					return $this->autoInsValidation( $value, ...$args );
				}
			);
		}
	}

	private function autoInsValidation( bool $expectedValue, Element $node ): bool {
		$dp = DOMDataUtils::getDataParsoid( $node );
		$autoInsEnd = isset( $dp->autoInsertedEnd );
		$this->assertEquals( $expectedValue, $autoInsEnd );
		return true;
	}

	/**
	 * test searching for autoInsertedEnd flags using DOM traversal helper functions
	 * @covers ::finalCleanup
	 * @dataProvider provideCleanUp
	 * @param string $test
	 */
	public function testfinalCleanUp( string $test ): void {
		// Cleanup DOM pass should confirm removal of autoInsertedEnd flag
		// for wikitext table tags without closing tag syntax using DOM traversal
		$body = $this->parseWT( $test );

		$domVisitor = new DOMTraverser();
		$tags = [ 'tr', 'td', ];
		$this->addHandlers( $domVisitor, $tags, false );

		$domVisitor->traverse( null, $body );
	}

	public function provideCleanUp(): array {
		$test = [
			"{|",
			"|a",
			"|}"
		];
		return [ [ implode( "\n", $test ) ] ];
	}

	/**
	 * test for autoInsertedEnd flags in all possible WT tags with no closing tags
	 * "PRE", "LI", "DT", "DD", "HR", "TR", "TD", "TH", "CAPTION"
	 * @covers ::finalCleanup
	 * @dataProvider provideCleanUpWT
	 * @param string $test
	 */
	public function testCleanUpWT( string $test ): void {
		// Cleanup DOM pass should confirm removal of autoInsertedEnd flag
		// for all wikitext tags without closing tags
		$body = $this->parseWT( $test );

		$domVisitor = new DOMTraverser();
		$tags = [ 'pre', 'li', 'dt', 'dd', 'hr', 'tr', 'td', 'th', 'caption' ];
		$this->addHandlers( $domVisitor, $tags, false );

		$domVisitor->traverse( null, $body );
	}

	public function provideCleanUpWT(): array {
		$test = [
			";Definition list",
			":First definition",
			":Second definition",
			"{|",
			"|+ caption",
			"|-",
			"! heading 1!! heading 2",
			"|-",
			"|a||b",
			"|}",
			" preformatted text using leading whitespace as a pre wikitext symbol equivalent",
			"{|",
			"|c",
			"|}",
			"# Item 1",
			"# Item 2",
			];
		return [ [ implode( "\n", $test ) ] ];
	}

	/**
	 * test for autoInsertedEnd flags in all possible HTML wikitext tags with no closing tags
	 * "PRE", "LI", "DT", "DD", "HR", "TR", "TD", "TH", "CAPTION"
	 * @covers ::finalCleanup
	 * @dataProvider provideCleanUpHTML
	 * @param string $test
	 */
	public function testCleanUpHTML( string $test ): void {
		// Cleanup DOM pass should confirm presence of autoInsertedEnd flag
		// for all HTML wikitext tags that can appear without closing tags
		$body = $this->parseWT( $test );

		$domVisitor = new DOMTraverser();
		$tags = [ 'pre', 'li', 'dt', 'dd', 'hr', 'tr', 'td', 'th', 'caption' ];
		$this->addHandlers( $domVisitor, $tags, true );

		$domVisitor->traverse( null, $body );
	}

	public function provideCleanUpHTML(): array {
		$test = [
			"<dl>",
			"<dt>Definition list",
			"<dd>First definition",
			"<dd>Second definition",
			"</dl>",
			"<table>",
			"<caption>caption",
			"<tr>",
			"<th>heading 1",
			"<th>heading 2",
			"<tr>",
			"<td>a",
			"<td>b",
			"</table>",
			"<pre>preformatted text using leading whitespace as a pre wikitext symbol equivalent",
			"<ol>",
			"<li>Item 1",
			"<li>Item 2",
			"</ol>",
		];
		return [ [ implode( "\n", $test ) ] ];
	}

	/**
	 * @param string $wt
	 * @param string $selector
	 * @param int $leadingWS
	 * @param int $trailingWS
	 * @dataProvider provideWhitespaceTrimming
	 * @covers ::trimWhiteSpace
	 */
	public function testWhitespaceTrimming( string $wt, string $selector, int $leadingWS, int $trailingWS ): void {
		$mockEnv = new MockEnv( [] );
		$body = $this->parseWT( $wt );
		$node = DOMCompat::querySelector( $body, $selector );
		$this->assertEquals( $leadingWS, DOMDataUtils::getDataParsoid( $node )->dsr->leadingWS );
		$this->assertEquals( $trailingWS, DOMDataUtils::getDataParsoid( $node )->dsr->trailingWS );
	}

	public function provideWhitespaceTrimming(): array {
		return [
			/* List item tests */
			[ "*a", "li", 0, 0 ],
			[ "* a", "li", 1, 0 ],
			[ "*    a  ", "li", 4, 2 ],
			[ "* <!--c-->a", "li", 1, 0 ],
			[ "* <!--c--> a", "li", -1, 0 ],
			[ "* <!--c--> a ", "li", -1, 1 ],
			[ "* a ", "li", 1, 1 ],
			[ "*a<!--c--> ", "li", 0, 1 ],
			[ "*a <!--c--> ", "li", 0, -1 ],
			[ "* [[Category:Foo]] a", "li", -1, 0 ],
			[ "* x[[Category:Foo]] ", "li", 1, 1 ],
			[ "* x [[Category:Foo]] ", "li", 1, -1 ],

			/* Heading tests */
			[ "==h==", "h2", 0, 0 ],
			[ "==  h   ==", "h2", 2, 3 ],
			[ "== <!--c-->h==", "h2", 1, 0 ],
			[ "== <!--c--> h ==", "h2", -1, 1 ],
			[ "== h<!--c--> ==", "h2", 1, 1 ],

			/* Table tests */
			[ "{|\n|x\n|}", "td", 0, 0 ],
			[ "{|\n| x|| y  \n|}", "td:first-child", 1, 0 ],
			[ "{|\n| x|| y  \n|}", "td:first-child + td", 1, 2 ],
			[ "{|\n| <!--c-->x\n|}", "td", 1, 0 ],
			[ "{|\n| <!--c--> x\n|}", "td", -1, 0 ],
			[ "{|\n| <!--c-->x<!--c--> \n|}", "td", 1, 1 ],
			[ "{|\n| <!--c--> x <!--c--> \n|}", "td", -1, -1 ],
		];
	}
}

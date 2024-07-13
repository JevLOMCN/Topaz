<?php

namespace Test\Parsoid\Wt2Html\PP\Handlers;

use PHPUnit\Framework\TestCase;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\Mocks\MockDataAccess;
use Wikimedia\Parsoid\Mocks\MockPageConfig;
use Wikimedia\Parsoid\Mocks\MockPageContent;
use Wikimedia\Parsoid\Mocks\MockSiteConfig;
use Wikimedia\Parsoid\Parsoid;
use Wikimedia\Parsoid\Utils\ContentUtils;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMDataUtils;

class UnpackDOMFragmentsTest extends TestCase {

	private function getOutput( string $wt ): Element {
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

	private function validateFixedupDSR( Element $body, ?Element $markerNode ): void {
		$links = DOMCompat::querySelectorAll( $body, 'a[rel~=mw:ExtLink]' );
		$count = 0;
		$extLink = null;
		foreach ( $links as $link ) {
			$dp = DOMDataUtils::getDataParsoid( $link );
			if ( !( $dp->misnested ?? false ) ) {
				$extLink = $link;
				$count++;
			}
		}

		// Assert we have exactly 1 extlink (that should be marked misnested);
		$this->assertSame( 1, $count );

		// Assert all nodes from extLink's next sibling till markerNode's previous sibling
		// have 'misnested' flags set and have non-null zero-range DSR.
		$n = $extLink->nextSibling;
		while ( $n !== $markerNode ) {
			if ( $n instanceof Element ) {
				$dp = DOMDataUtils::getDataParsoid( $n );
				$dsr = $dp->dsr ?? null;

				$this->assertTrue( $dp->misnested );
				$this->assertNotNull( $dsr );
				$this->assertSame( $dsr->end, $dsr->start );
			}

			$n = $n->nextSibling;
		}

		// Assert that nothing beyond the marker node has 'misnested' flags set.
		while ( $n ) {
			if ( $n instanceof Element ) {
				$dp = DOMDataUtils::getDataParsoid( $n );
				$this->assertFalse( $dp->misnested ?? false );
			}
			$n = $n->nextSibling;
		}
	}

	/**
	 * @covers \Wikimedia\Parsoid\Wt2Html\PP\Handlers\UnpackDOMFragments
	 * @dataProvider provideFixMisnestedTagDSRCases
	 * @param string $wt
	 * @param string|null $marker
	 */
	public function testFixMisnestedTagDSRCases( string $wt, ?string $marker ): void {
		// Strictly speaking, *NOT* a unit test, but we are
		// abusing this notion for verification of properties
		// not easily verifiable via parser tests.
		$body = $this->getOutput( $wt );
		$markerNode = $marker ? DOMCompat::getElementById( $body->ownerDocument, $marker ) : null;
		$this->validateFixedupDSR( $body, $markerNode );
	}

	public function provideFixMisnestedTagDSRCases(): array {
		return [
			[ "[http://example.org Link with [[wikilink]] link in the label]", null ],
			[ "[http://example.org <span>[[wikilink]]</span> link in the label]", null ],
			[ "[http://example.org <div>[[wikilink]]</div> link in the label]", null ],
			[ "[http://example.org <b>''[[wikilink]]''</b> link in the label]", null ],
			// phpcs:ignore Generic.Files.LineLength.TooLong
			[ "[http://example.org <b>''[[wikilink]]''</b> link in the label] <span id='marker'></span> and ''stuff''", 'marker' ],
			[ "* [http://example.org [[wikilink]]] <span id='marker'></span>foo ''bar''", 'marker' ]
		];
	}

}

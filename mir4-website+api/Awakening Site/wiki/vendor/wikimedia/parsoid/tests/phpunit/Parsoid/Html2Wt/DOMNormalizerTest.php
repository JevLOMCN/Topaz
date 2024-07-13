<?php

namespace Test\Parsoid\Html2Wt;

use PHPUnit\Framework\TestCase;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\Html2Wt\DiffUtils;
use Wikimedia\Parsoid\Html2Wt\DOMNormalizer;
use Wikimedia\Parsoid\Html2Wt\SerializerState;
use Wikimedia\Parsoid\Html2Wt\WikitextSerializer;
use Wikimedia\Parsoid\Mocks\MockEnv;
use Wikimedia\Parsoid\Utils\ContentUtils;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMUtils;
use Wikimedia\TestingAccessWrapper;

/**
 * Test DOM normalisation, the tests used for Porting DOMNormalizer class from JS
 * and based on similar tests in tests/mocha/dom_normalizer.js
 * @coversDefaultClass \Wikimedia\Parsoid\Html2Wt\DOMNormalizer
 */
class DOMNormalizerTest extends TestCase {

	/**
	 * @covers ::normalize
	 * @dataProvider provideNormalize
	 * @param string $html
	 * @param string $expected
	 * @param string|null $message
	 * @param array $opts
	 * @param bool $stripDiffMarkers
	 */
	public function testNormalize(
		string $html, string $expected, ?string $message = null, array $opts = [], bool $stripDiffMarkers = true
	) {
		$mockEnv = new MockEnv( $opts );
		$mockSerializer = new WikitextSerializer( $mockEnv, [] );
		$mockState = new SerializerState( $mockSerializer, [ 'selserMode' => false ] );
		/** @var DOMNormalizer $DOMNormalizer */
		$DOMNormalizer = TestingAccessWrapper::newFromObject( new DOMNormalizer( $mockState ) );
		$doc = ContentUtils::createAndLoadDocument( $html, [ 'markNew' => true ] );
		$body = DOMCompat::getBody( $doc );
		$DOMNormalizer->normalize( $body );

		if ( $stripDiffMarkers ) {
			DOMUtils::visitDOM( $body, static function ( Node $node ) {
				if ( DiffUtils::isDiffMarker( $node ) ) {
					$node->parentNode->removeChild( $node );
				}
			} );
		}

		$actual = ContentUtils::ppToXML( $body, [ 'discardDataParsoid' => true, 'innerXML' => true ] );
		$this->assertEquals( $expected, $actual, $message );
	}

	public function provideNormalize(): array {
		return [
			// Tag Minimization
			[ '<i>X</i><i>Y</i>', '<i>XY</i>', 'Tag Minimization #1', ],
			[
				'<i>X</i><b><i>Y</i></b>',
				'<i>X<b>Y</b></i>',
				'Tag Minimization #2',
			],
			[
				'<i>A</i><b><i>X</i></b><b><i>Y</i></b><i>Z</i>',
				'<i>A<b>XY</b>Z</i>',
				'Tag Minimization #3',
			],
			[
				// Second node is a newly inserted node
				'<a data-parsoid="{}" href="FootBall">Foot</a><a href="FootBall">Ball</a>',
				'<a href="FootBall">FootBall</a>',
				'Tag Minimization #4 Second node is a newly inserted node',
			],
			[
				// Both nodes are old unedited nodes
				'<a data-parsoid="{}" href="FootBall">Foot</a><a data-parsoid="{}" href="FootBall">Ball</a>',
				'<a href="FootBall">Foot</a><a href="FootBall">Ball</a>',
				'Tag Minimization #5 Both nodes are old unedited nodes',

			],
			// Headings
			[
				'<h2>H2<link href="Category:A1" rel="mw:PageProp/Category"/></h2>',
				'<h2>H2</h2><link href="Category:A1" rel="mw:PageProp/Category"/>',
				'Headings #1'
			],
			[
				'<h2><meta property="mw:PageProp/toc"/> ok</h2>',
				'<meta property="mw:PageProp/toc"/><h2>ok</h2>',
				'Headings #2'
			],
			// Empty tag normalization
			// These are stripped
			[ '<b></b>', '', 'Empty tag normalization #1' ],
			[ '<i></i>', '', 'Empty tag normalization #2' ],
			[ '<h2></h2>', '', 'Empty tag normalization #3' ],
			[ '<a rel="mw:WikiLink" href="http://foo.org"></a>', '', 'Empty tag normalization #4' ],
			// These should not be stripped
			[ '<p></p>', '<p></p>', 'Empty tag normalization #5' ],
			[ '<div></div>', '<div></div>', 'Empty tag normalization #6' ],
			[
				'<a href="http://foo.org"></a>',
				'<a href="http://foo.org"></a>',
				'Empty tag normalization #7',
			],
			[
				'<p><meta typeof="mw:Annotation/translate"/></p>',
				'<meta typeof="mw:Annotation/translate"/>',
				'Normalization of annotations in paragraph',
			],
			// Trailing spaces in links
			[
				'<a rel="mw:WikiLink" href="./Foo">Foo </a>',
				'<a rel="mw:WikiLink" href="./Foo">Foo</a>',
				'Trailing spaces in links #1',
			],
			[
				'<a rel="mw:WikiLink" href="./Foo">Foo </a>bar',
				'<a rel="mw:WikiLink" href="./Foo">Foo</a> bar',
				'Trailing spaces in links #2',
			],
			[
				'<a rel="mw:WikiLink" href="./Foo">Foo </a> bar',
				'<a rel="mw:WikiLink" href="./Foo">Foo</a> bar',
				'Trailing spaces in links #3',
			],
			// Formatting tags in links
			[
				'<a rel="mw:WikiLink" href="./Football"><u><i><b>Football</b></i></u></a>',
				'<u><i><b><a rel="mw:WikiLink" href="./Football">Football</a></b></i></u>',
				'Formatting tags in links #1 Reordered HTML serializable to simplified form',
			],
			[
				'<a rel="mw:WikiLink" href="./Football"><i color="brown">Football</i></a>',
				'<a rel="mw:WikiLink" href="./Football"><i color="brown">Football</i></a>',
				'Formatting tags in links #2 Reordered HTML changes semantics',
			],
			[
				'<a rel="mw:WikiLink" href="./Football"><u><i><b>Soccer</b></i></u></a>',
				'<a rel="mw:WikiLink" href="./Football"><u><i><b>Soccer</b></i></u></a>',
				'Formatting tags in links #3 Reordered HTML NOT serializable to simplified form',
			],
			[
				'<table><tbody><tr><td>+</td><td>-</td></tr></tbody></table>',
				'<table><tbody><tr><td> +</td><td> -</td></tr></tbody></table>',
				'Escapable prefixes in table cells'
			],
		];
	}

}

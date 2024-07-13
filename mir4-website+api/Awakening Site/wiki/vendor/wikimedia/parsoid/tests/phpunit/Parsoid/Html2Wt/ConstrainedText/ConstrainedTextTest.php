<?php
namespace Test\Parsoid\Html2Wt\ConstrainedText;

use Wikimedia\Parsoid\Html2Wt\ConstrainedText\AutoURLLinkText;
use Wikimedia\Parsoid\Html2Wt\ConstrainedText\ConstrainedText;
use Wikimedia\Parsoid\Html2Wt\ConstrainedText\ExtLinkText;
use Wikimedia\Parsoid\Html2Wt\ConstrainedText\LanguageVariantText;
use Wikimedia\Parsoid\Html2Wt\ConstrainedText\MagicLinkText;
use Wikimedia\Parsoid\Html2Wt\ConstrainedText\WikiLinkText;
use Wikimedia\Parsoid\Mocks\MockEnv;
use Wikimedia\Parsoid\Utils\ContentUtils;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMDataUtils;
use Wikimedia\Parsoid\Utils\DOMUtils;

/**
 * @coversDefaultClass \Wikimedia\Parsoid\Html2Wt\ConstrainedText\ConstrainedText
 */
class ConstrainedTextTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @covers ::fromSelSer
	 * @covers ::fromSelSerImpl
	 * @covers ::escape
	 * @covers ::escapeLine
	 * @dataProvider provideConstrainedText
	 */
	public function testConstrainedText( array $t ) {
		$t = (object)$t;
		// Set up environment and test data
		$env = new MockEnv( [
			'linkPrefixRegex' => $t->linkPrefixRegex ?? null,
			'linkTrailRegex' => $t->linkTrailRegex ?? null,
		] );
		$doc = ContentUtils::createAndLoadDocument( $t->html );
		$node = DOMCompat::getBody( $doc )->firstChild;
		DOMUtils::assertElt( $node );
		$dataParsoid = DOMDataUtils::getDataParsoid( $node );

		// Test ConstrainedText.fromSelSer
		$ct = ConstrainedText::fromSelSer( $t->text, $node, $dataParsoid, $env );
		$this->assertIsArray( $ct );
		$this->assertSameSize( $t->types, $ct );
		$actualNames = array_map( static function ( $x ) {
			return get_class( $x );
		}, $ct );
		foreach ( $t->types as $i => $name ) {
			$this->assertEquals( $name, $actualNames[$i] );
		}

		// Test ConstrainedText::escapeLine
		foreach ( $t->escapes as $e ) {
			$e = (object)$e;
			$nct = $ct; // copy
			if ( isset( $e->left ) ) {
				$n = $node->ownerDocument->createTextNode( $e->left );
				array_unshift( $nct, ConstrainedText::cast( $e->left, $n ) );
			}
			if ( isset( $e->right ) ) {
				$n = $node->ownerDocument->createTextNode( $e->right );
				$nct[] = ConstrainedText::cast( $e->right, $n );
			}
			$r = ConstrainedText::escapeLine( $nct );
			$this->assertEquals( $e->output, $r );
		}
	}

	// phpcs:disable Generic.Files.LineLength.TooLong
	public function provideConstrainedText(): array {
		return [
			[ [
				'name' => 'WikiLinkText: Simple',
				'linkTrailRegex' => /* RegExp */ '/^([a-z]+)/', // enwiki
				'html' => "<a rel=\"mw:WikiLink\" href=\"./Foo\" title=\"Foo\" data-parsoid='{\"stx\":\"simple\",\"a\":{\"href\":\"./Foo\"},\"sa\":{\"href\":\"Foo\"}}'>Foo</a>",
				'types' => [ WikiLinkText::class ],
				'text' => '[[Foo]]',
				'escapes' => [
					[
						'output' => '[[Foo]]'
					],
					[
						'left' => 'bar ',
						'right' => ' bat',
						'output' => 'bar [[Foo]] bat'
					],
					[
						'left' => '[',
						'right' => ']',
						'output' => '[<nowiki/>[[Foo]]]'
					],
					[
						// not a link trail
						'right' => "'s",
						'output' => "[[Foo]]'s"
					],
					[
						// a link trail
						'right' => 's',
						'output' => '[[Foo]]<nowiki/>s'
					]
				]
			] ],
			[ [
				'name' => 'WikiLinkText: iswiki linkprefix/linktrail',
				'linkPrefixRegex' => /* RegExp */ "/[áÁðÐéÉíÍóÓúÚýÝþÞæÆöÖA-Za-z–-]+\$/",
				'linkTrailRegex' => /* RegExp */ "/^([áðéíóúýþæöa-z-–]+)/",
				'html' => "<a rel=\"mw:WikiLink\" href=\"./Foo\" title=\"Foo\" data-parsoid='{\"stx\":\"simple\",\"a\":{\"href\":\"./Foo\"},\"sa\":{\"href\":\"Foo\"}}'>Foo</a>",
				'types' => [ WikiLinkText::class ],
				'text' => '[[Foo]]',
				'escapes' => [
					[
						'left' => 'bar ',
						'right' => ' bat',
						'output' => 'bar [[Foo]] bat'
					],
					[
						'left' => '-',
						'right' => '-',
						'output' => '-<nowiki/>[[Foo]]<nowiki/>-'
					]
				]
			] ],
			[ [
				'name' => 'WikiLinkText: iswiki greedy linktrails',
				'linkPrefixRegex' => /* RegExp */ "/[áÁðÐéÉíÍóÓúÚýÝþÞæÆöÖA-Za-z–-]+\$/",
				'linkTrailRegex' => /* RegExp */ "/^([áðéíóúýþæöa-z-–]+)/",
				'html' => "<p data-parsoid='{\"dsr\":[0,11,0,0]}'><a rel=\"mw:WikiLink\" href=\"./A\" title=\"A\" data-parsoid='{\"stx\":\"simple\",\"a\":{\"href\":\"./A\"},\"sa\":{\"href\":\"a\"},\"dsr\":[0,6,2,3],\"tail\":\"-\"}'>a-</a><a rel=\"mw:WikiLink\" href=\"./B\" title=\"B\" data-parsoid='{\"stx\":\"simple\",\"a\":{\"href\":\"./B\"},\"sa\":{\"href\":\"b\"},\"dsr\":[6,11,2,2]}'>b</a></p>",
				'types' => [
					ConstrainedText::class,
					WikiLinkText::class,
					ConstrainedText::class,
					WikiLinkText::class,
				],
				'text' => '[[a]]-[[b]]',
				'escapes' => [ [
						// this would be '[[a]]-<nowiki/>[[b]] if the "greedy"
						// functionality wasn't present; see commit
						// 88605a4a7a37a61da76238db6d3fff756e8514f1
						'output' => '[[a]]-[[b]]'
					]
				]
			] ],
			[ [
				'name' => 'ExtLinkText',
				'html' => "<a rel=\"mw:ExtLink\" href=\"https://example.com\" class=\"external autonumber\"></a>",
				'types' => [
					ExtLinkText::class,
				],
				'text' => '[https://example.com]',
				'escapes' => [
					[
						// ExtLinkText isn't very interesting
						'output' => '[https://example.com]'
					],
					[
						'left' => '[',
						'right' => ']',
						// FIXME This output is wrong! See: T220018
						'output' => '[[https://example.com]]'
					]
				]
			] ],
			[ [
				'name' => 'AutoURLLinkText: no paren',
				'html' => "<a rel=\"mw:ExtLink\" href=\"http://example.com\" class=\"external free\" data-parsoid='{\"stx\":\"url\",\"dsr\":[0,18,0,0]}'>http://example.com</a>",
				'types' => [
					AutoURLLinkText::class
				],
				'text' => 'https://example.com',
				'escapes' => [
					[
						'output' => 'https://example.com'
					],
					[
						// Non-word characters are find in the prefix
						'left' => '(',
						'output' => '(https://example.com'
					],
					[
						// Word characters need an escape
						'left' => 'foo',
						'right' => 'bar',
						'output' => 'foo<nowiki/>https://example.com<nowiki/>bar'
					],
					[
						// Close paren is fine in the trailing context so long
						// as the URL doesn't have a paren.
						'left' => '(',
						'right' => ')',
						'output' => '(https://example.com)'
					],
					[
						// Ampersand isn't allowed in the trailing context...
						'right' => '&',
						'output' => 'https://example.com<nowiki/>&'
					],
					[
						// ...but an entity will terminate the autourl
						'right' => '&lt;',
						'output' => 'https://example.com&lt;'
					],
					[
						// Single quote isn't allowed...
						'right' => "'",
						'output' => "https://example.com<nowiki/>'"
					],
					[
						// ...but double-quote (aka bold or italics) is fine
						'left' => "''",
						'right' => "''",
						'output' => "''https://example.com''"
					],
					[
						// Punctuation is okay.
						'right' => '.',
						'output' => 'https://example.com.'
					],
					[
						'left' => '[',
						'right' => ' foo]',
						// FIXME This output is wrong! See: T220018
						'output' => '[https://example.com foo]'
					]
				]
			] ],
			[ [
				'name' => 'AutoURLLinkText: w/ paren',
				'html' => "<a rel=\"mw:ExtLink\" href=\"http://example.com/foo(bar\" class=\"external free\" data-parsoid='{\"stx\":\"url\",\"dsr\":[0,26,0,0]}'>http://example.com/foo(bar</a></p>",
				'types' => [
					AutoURLLinkText::class
				],
				'text' => 'https://example.com/foo(bar',
				'escapes' => [
					[
						'output' => 'https://example.com/foo(bar'
					],
					[
						// Close paren is escaped in the trailing context since
						// the URL has a paren.
						'left' => '(',
						'right' => ')',
						'output' => '(https://example.com/foo(bar<nowiki/>)'
					]
				]
			] ],
			[ [
				'name' => 'AutoURLLinkText: w/ ampersand',
				'html' => "<a rel=\"mw:ExtLink\" href=\"http://example.com?foo&amp;lt\" class=\"external free\" data-parsoid='{\"stx\":\"url\",\"dsr\":[0,25,0,0]}'>http://example.com?foo&amp;lt</a>",
				'types' => [
					AutoURLLinkText::class
				],
				'text' => 'https://example.com?foo&lt',
				'escapes' => [
					[
						'output' => 'https://example.com?foo&lt'
					],
					[
						'right' => '.',
						'output' => 'https://example.com?foo&lt.'
					],
					[
						// Careful of right contexts which could complete an
						// entity
						'right' => ';',
						'output' => 'https://example.com?foo&lt<nowiki/>;'
					]
				]
			] ],
			[ [
				'name' => 'MagicLinkText',
				'html' => "<a href=\"./Special:BookSources/1234567890\" rel=\"mw:WikiLink\" data-parsoid='{\"stx\":\"magiclink\",\"dsr\":[0,15,2,2]}'>ISBN 1234567890</a>",
				'types' => [
					MagicLinkText::class
				],
				'text' => 'ISBN 1234567890',
				'escapes' => [
					[
						'output' => 'ISBN 1234567890'
					],
					[
						'left' => 'I',
						'right' => '1',
						'output' => 'I<nowiki/>ISBN 1234567890<nowiki/>1'
					]
				]
			] ],
			[ [
				'name' => 'LanguageVariantText',
				'html' => "<span typeof=\"mw:LanguageVariant\" data-mw-variant='{\"disabled\":{\"t\":\"raw\"}}' data-parsoid='{\"fl\":[],\"src\":\"-{raw}-\",\"dsr\":[0,7,null,2]}'></span>",
				'types' => [
					LanguageVariantText::class
				],
				'text' => '-{raw}-',
				'escapes' => [
					[
						'output' => '-{raw}-'
					],
					[
						// single | at SOL causes issues with table markup
						'left' => '|',
						'output' => '|<nowiki/>-{raw}-'
					],
					[
						'left' => '||',
						'output' => '||-{raw}-'
					]
				]
			] ],
		];
	}
}

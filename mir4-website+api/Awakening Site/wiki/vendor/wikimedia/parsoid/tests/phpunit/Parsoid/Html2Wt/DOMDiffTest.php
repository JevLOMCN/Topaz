<?php

namespace Test\Parsoid\Html2Wt;

use PHPUnit\Framework\TestCase;
use Wikimedia\Parsoid\Html2Wt\DiffMarkers;
use Wikimedia\Parsoid\Html2Wt\DiffUtils;
use Wikimedia\Parsoid\Html2Wt\DOMDiff;
use Wikimedia\Parsoid\Mocks\MockEnv;
use Wikimedia\Parsoid\Utils\ContentUtils;
use Wikimedia\Parsoid\Utils\DOMCompat;

/**
 * Test DOM Diff, the tests used for validating DOMNDiff class port from JS
 * and based on similar tests in tests/mocha/domdiff.js
 * @coversDefaultClass \Wikimedia\Parsoid\Html2Wt\DOMDiff
 */
class DOMDiffTest extends TestCase {

	/**
	 * @covers ::doDOMDiff
	 * @dataProvider provideDiff
	 * @param array $test
	 */
	public function testDOMDiff( array $test ) {
		$mockEnv = new MockEnv( [] );

		$oldDOM = ContentUtils::createAndLoadDocument(
			$test['orig'], [ 'markNew' => true ]
		);
		$newDOM = ContentUtils::createAndLoadDocument(
			$test['edit'], [ 'markNew' => true ]
		);

		$oldBody = DOMCompat::getBody( $oldDOM );
		$body = DOMCompat::getBody( $newDOM );

		$domDiff = new DOMDiff( $mockEnv );
		$domDiff->diff( $oldBody, $body );

		foreach ( $test['specs'] as $spec ) {
			if ( $spec['selector'] === 'body' ) { // Hmm .. why is this?
				$node = $body;
			} else {
				$nodes = DOMCompat::querySelectorAll( $body, $spec['selector'] );
				$this->assertCount( 1, $nodes );
				$node = $nodes[0];
			}
			if ( isset( $spec['diff'] ) ) {
				$this->assertTrue( DiffUtils::isDiffMarker( $node, $spec['diff'] ) );
			} elseif ( isset( $spec['markers'] ) ) {
				$markers = DiffUtils::getDiffMark( $node )->diff ?? [];

				$this->assertSameSize( $spec['markers'], $markers,
					'number of markers does not match' );

				foreach ( $markers as $k => $m ) {
					$this->assertEquals( $spec['markers'][$k], $m,
						'markers do not match' );
				}
			}
		}
	}

	/**
	 * FIXME: The subtree-changed marker seems to be applied inconsistently.
	 * Check if that marker is still needed / used by serialization code and
	 * update code accordingly. If possible, simplify / reduce the different
	 * markers being used.
	 */
	public function provideDiff(): array {
		return [
			[
				[
					'desc' => 'ignore attribute order in a node',
					'orig' => '<font size="1" class="x">foo</font>',
					'edit' => '<font class="x" size="1">foo</font>',
					'specs' => [
						[ 'selector' => 'font', 'markers' => [] ],
					]
				]
			],
			[
				[
					'desc' => 'changing text in a node',
					'orig' => '<p>a</p><p>b</p>',
					'edit' => '<p>A</p><p>b</p>',
					'specs' => [
						[ 'selector' => 'body > p:first-child', 'markers' => [ DiffMarkers::CHILDREN_CHANGED,
							DiffMarkers::SUBTREE_CHANGED ] ],
						[ 'selector' => 'body > p:first-child > meta:first-child', 'diff' => DiffMarkers::DELETED ]
					]
				]
			],
			[
				[
					'desc' => 'deleting a node',
					'orig' => '<p>a</p><p>b</p>',
					'edit' => '<p>a</p>',
					'specs' => [
						[ 'selector' => 'body', 'markers' => [ DiffMarkers::CHILDREN_CHANGED ] ],
						[ 'selector' => 'body > p + meta', 'diff' => DiffMarkers::DELETED ]
					]
				]
			],
			[
				[
					'desc' => 'adding multiple nodes',
					'orig' => '<p>a</p>',
					'edit' => '<p>x</p><p>a</p><p>y</p>',
					'specs' => [
						[ 'selector' => 'body', 'markers' => [ DiffMarkers::CHILDREN_CHANGED ] ],
						[ 'selector' => 'body > p:nth-child(1)', 'markers' => [ DiffMarkers::CHILDREN_CHANGED,
							DiffMarkers::SUBTREE_CHANGED ] ],
						[ 'selector' => 'body > p:nth-child(1) > meta', 'diff' => DiffMarkers::DELETED ],
						[ 'selector' => 'body > p:nth-child(2)', 'markers' => [ DiffMarkers::INSERTED ] ],
						[ 'selector' => 'body > p:nth-child(3)', 'markers' => [ DiffMarkers::INSERTED ] ]
					]
				]
			],
			[
				[
					'desc' => 'reordering nodes',
					'orig' => '<p>a</p><p>b</p>',
					'edit' => '<p>b</p><p>a</p>',
					'specs' => [
						[ 'selector' => 'body > p:nth-child(1)', 'markers' => [ DiffMarkers::CHILDREN_CHANGED,
							DiffMarkers::SUBTREE_CHANGED ] ],
						[ 'selector' => 'body > p:nth-child(1) > meta', 'diff' => DiffMarkers::DELETED ],
						[ 'selector' => 'body > p:nth-child(2)', 'markers' => [ DiffMarkers::CHILDREN_CHANGED,
							DiffMarkers::SUBTREE_CHANGED ] ],
						[ 'selector' => 'body > p:nth-child(2) > meta', 'diff' => DiffMarkers::DELETED ]
					]
				]
			],
			[
				[
					'desc' => 'adding and deleting nodes',
					'orig' => '<p>a</p><p>b</p><p>c</p>',
					'edit' => '<p>x</p><p>b</p>',
					'specs' => [
						[ 'selector' => 'body', 'markers' => [ DiffMarkers::CHILDREN_CHANGED ] ],
						[ 'selector' => 'body > p:nth-child(1)', 'markers' => [ DiffMarkers::CHILDREN_CHANGED,
							DiffMarkers::SUBTREE_CHANGED ] ],
						[ 'selector' => 'body > p:nth-child(1) > meta', 'diff' => DiffMarkers::DELETED ],
						[ 'selector' => 'body > meta:nth-child(3)', 'diff' => DiffMarkers::DELETED ]
					]
				]
			],
			[
				[
					'desc' => 'changing an attribute',
					'orig' => '<p class="a">a</p><p class="b">b</p>',
					'edit' => '<p class="X">a</p><p class="b">b</p>',
					'specs' => [
						[ 'selector' => 'body', 'markers' => [ DiffMarkers::CHILDREN_CHANGED ] ],
						[ 'selector' => 'body > p:nth-child(1)', 'markers' => [ DiffMarkers::MODIFIED_WRAPPER ] ]
					]
				]
			],
			[
				[
					'desc' => 'changing data-mw for a template',
					'orig' => '<p about="#mwt1" typeof="mw:Transclusion" data-mw=\'{"parts":[{"template":' .
						'{"target":{"wt":"1x","href":"./Template:1x"},"params":{"1":{"wt":"a"}},"i":0}}]}\'>a</p>',
					'edit' => '<p about="#mwt1" typeof="mw:Transclusion" data-mw=\'{"parts":[{"template":' .
						'{"target":{"wt":"1x","href":"./Template:1x"},"params":{"1":{"wt":"foo"}},"i":' .
						'0}}]}\'>foo</p>',
					'specs' => [
						[ 'selector' => 'body', 'markers' => [ DiffMarkers::CHILDREN_CHANGED ] ],
						[ 'selector' => 'body > p:nth-child(1)', 'markers' => [ DiffMarkers::MODIFIED_WRAPPER ] ],
					]
				]
			],
			// The additional subtrees added to the template's content should simply be ignored
			[
				[
					'desc' => 'adding additional DOM trees to templated content',
					'orig' => '<p about="#mwt1" typeof="mw:Transclusion" data-mw=\'{"parts":[{"template":' .
						'{"target":{"wt":"1x","href":"./Template:1x"},"params":{"1":{"wt":"a"}},"i":0}}]}\'>a</p>',
					'edit' => '<p about="#mwt1" typeof="mw:Transclusion" data-mw=\'{"parts":[{"template":' .
						'{"target":{"wt":"1x","href":"./Template:1x"},"params":{"1":{"wt":' .
						'"foo\\n\\nbar\\n\\nbaz"}},' .
						'"i":0}}]}\'>foo</p><p about="#mwt1">bar</p><p about="#mwt1">baz</p>',
					'specs' => [
						[ 'selector' => 'body', 'markers' => [ DiffMarkers::CHILDREN_CHANGED ] ],
						[ 'selector' => 'body > p:nth-child(1)', 'markers' => [ DiffMarkers::MODIFIED_WRAPPER ] ],
					]
				]
			]
		];
	}

}

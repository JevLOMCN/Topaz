<?php

namespace Test\Parsoid\Utils;

use Wikimedia\Parsoid\Config\Env;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\Ext\ParsoidExtensionAPI;
use Wikimedia\Parsoid\Mocks\MockEnv;
use Wikimedia\Parsoid\Utils\ContentUtils;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMTraverser;
use Wikimedia\Parsoid\Utils\DTState;

class DOMTraverserTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @phpcs:disable Generic.Files.LineLength.TooLong
	 * @dataProvider provideTraverse
	 * @covers \Wikimedia\Parsoid\Utils\DOMTraverser::addHandler
	 * @covers \Wikimedia\Parsoid\Utils\DOMTraverser::traverse
	 */
	public function testTraverse(
		callable $callback, ?string $nodeName, Env $env, array $expectedTrace,
		bool $withTplInfo = false, bool $processAttrEmbeddedHTML = false
	) {
		$html = <<<'HTML'
<html><body>
	<div id="x1">
		<div id="x1_1" typeof="mw:Transclusion" about="#mwt1">
		</div><blockquote id="x1_2" about="#mwt1">
			<div id="x1_2_1"></div>
			<div id="x1_2_2"></div>
		</blockquote>
		<div id="x1_3"></div>
	</div>
	<div id="x2">
		<div id="x2_1"></div>
	</div>
	<!--dummy expanded attrs representation to exercise embedded html code-->
	<!--no id on this div so we don't have to update all tests, but add id on the embedded span-->
	<div typeof='mw:ExpandedAttrs' data-mw='{"attribs": [[{"txt": "foo"},{"html": "<span id=\"e_span\">x</span>"}]]}'>x</div>
</body></html>
HTML;
		if ( $withTplInfo || $processAttrEmbeddedHTML ) {
			$doc = ContentUtils::createAndLoadDocument( $html );
		} else {
			$doc = DOMCompat::newDocument( true );
			$doc->loadHTML( $html );
		}

		$state = new DTState( [], true );
		$trace = [];

		$traverser = new DOMTraverser( $withTplInfo, $processAttrEmbeddedHTML );
		$traverser->addHandler( $nodeName, $callback );
		$traverser->addHandler( null, static function ( Node $node, DTState $state ) use ( &$trace ) {
			if ( $node instanceof Element && $node->hasAttribute( 'id' ) ) {
				$trace[] = DOMCompat::getAttribute( $node, 'id' );
			}
			return true;
		} );
		$traverser->traverse( new ParsoidExtensionAPI( $env ), $doc->documentElement, $state );
		$this->assertSame( $expectedTrace, $trace );
	}

	public function provideTraverse() {
		$basicEnv = new MockEnv( [] );

		$expectError = $this->getMockBuilder( MockEnv::class )
			->setConstructorArgs( [ [] ] )
			->onlyMethods( [ 'log' ] )
			->getMock();
		$expectError->expects( $this->atLeastOnce() )
			->method( 'log' )
			->willReturnCallback( function ( $prefix ) {
				$this->assertSame( 'error', $prefix );
			} );
		$dontExpectError = $this->getMockBuilder( MockEnv::class )
			->setConstructorArgs( [ [] ] )
			->onlyMethods( [ 'log' ] )
			->getMock();
		$dontExpectError->expects( $this->never() )
			->method( 'log' );

		return [
			'basic' => [
				'callback' => function ( Node $node, ?DTState $state ) use ( $basicEnv ) {
					$this->assertTrue( $state->atTopLevel );
					return true;
				},
				'nodeName' => null,
				'env' => $basicEnv,
				'expectedTrace' => [ 'x1', 'x1_1', 'x1_2', 'x1_2_1', 'x1_2_2', 'x1_3', 'x2', 'x2_1' ],
			],
			'return true' => [
				'callback' => static function ( Node $node ) {
					return true;
				},
				'nodeName' => null,
				'env' => $basicEnv,
				'expectedTrace' => [ 'x1', 'x1_1', 'x1_2', 'x1_2_1', 'x1_2_2', 'x1_3', 'x2', 'x2_1' ],
			],
			'return first child' => [
				'callback' => static function ( Node $node ) {
					if ( $node instanceof Element && DOMCompat::getAttribute( $node, 'id' ) === 'x1_2' ) {
						return $node->firstChild;
					}
					return true;
				},
				'nodeName' => null,
				'env' => $basicEnv,
				'expectedTrace' => [ 'x1', 'x1_1', 'x1_2_1', 'x1_2_2', 'x2', 'x2_1' ],
			],
			'return next sibling' => [
				'callback' => static function ( Node $node ) {
					if ( $node instanceof Element && DOMCompat::getAttribute( $node, 'id' ) === 'x1_2' ) {
						return $node->nextSibling;
					}
					return true;
				},
				'nodeName' => null,
				'env' => $basicEnv,
				'expectedTrace' => [ 'x1', 'x1_1', 'x1_3', 'x2', 'x2_1' ],
			],
			'return null' => [
				'callback' => static function ( Node $node ) {
					if ( $node instanceof Element && DOMCompat::getAttribute( $node, 'id' ) === 'x1_2' ) {
						return null;
					}
					return true;
				},
				'nodeName' => null,
				'env' => $basicEnv,
				'expectedTrace' => [ 'x1', 'x1_1', 'x2', 'x2_1' ],
			],
			'return another node' => [
				'callback' => static function ( Node $node ) {
					if ( $node instanceof Element && DOMCompat::getAttribute( $node, 'id' ) === 'x1_2' ) {
						$newNode = $node->ownerDocument->createElement( 'div' );
						$newNode->setAttribute( 'id', 'new' );
						return $newNode;
					}
					return true;
				},
				'nodeName' => null,
				'env' => $basicEnv,
				'expectedTrace' => [ 'x1', 'x1_1', 'new', 'x2', 'x2_1' ],
			],
			'name filter' => [
				'callback' => static function ( Node $node ) {
					if ( $node instanceof Element && DOMCompat::getAttribute( $node, 'id' ) === 'x1_2' ) {
						return null;
					}
					return true;
				},
				'nodeName' => 'div',
				'env' => $basicEnv,
				'expectedTrace' => [ 'x1', 'x1_1', 'x1_2', 'x1_2_1', 'x1_2_2', 'x1_3', 'x2', 'x2_1' ],
			],
			'not traversing with tplinfo' => [
				'callback' => function ( Node $node, DTState $state ) {
					if ( $node instanceof Element && DOMCompat::getAttribute( $node, 'id' ) === 'x1_1' ) {
						$this->assertTrue( $state->tplInfo === null );
					}
					return true;
				},
				'nodeName' => null,
				'env' => $basicEnv,
				'expectedTrace' => [ 'x1', 'x1_1', 'x1_2', 'x1_2_1', 'x1_2_2', 'x1_3', 'x2', 'x2_1' ],
			],
			'traversing with tplinfo' => [
				'callback' => function ( Node $node, DTState $state ) {
					if ( $node instanceof Element && DOMCompat::getAttribute( $node, 'id' ) === 'x1_1' ) {
						$this->assertTrue( $state->tplInfo->first === $node );
					}
					if ( $node instanceof Element && DOMCompat::getAttribute( $node, 'id' ) === 'x1_2' ) {
						$this->assertTrue( $state->tplInfo->last === $node );
					}
					if ( $node instanceof Element && DOMCompat::getAttribute( $node, 'id' ) === 'x1_2_1' ) {
						$this->assertTrue( $state->tplInfo !== null );
					}
					if ( $node instanceof Element && DOMCompat::getAttribute( $node, 'id' ) === 'x1_3' ) {
						$this->assertTrue( $state->tplInfo === null );
					}
					return true;
				},
				'nodeName' => null,
				'env' => $basicEnv,
				'expectedTrace' => [ 'x1', 'x1_1', 'x1_2', 'x1_2_1', 'x1_2_2', 'x1_3', 'x2', 'x2_1' ],
				'withTplInfo' => true,
			],
			'not traversing with tplinfo, with embedded html' => [
				'callback' => function ( Node $node, DTState $state ) {
					if ( $node instanceof Element && DOMCompat::getAttribute( $node, 'id' ) === 'x1_1' ) {
						$this->assertTrue( $state->tplInfo === null );
					}
					return true;
				},
				'nodeName' => null,
				'env' => $basicEnv,
				'expectedTrace' => [ 'x1', 'x1_1', 'x1_2', 'x1_2_1', 'x1_2_2', 'x1_3', 'x2', 'x2_1', 'e_span' ],
				false,
				true /* process attribute embedded html */
			],
		];
	}
}

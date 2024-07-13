<?php

namespace Test\Parsoid\Utils;

use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMPostOrder;

class DOMPostOrderTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @covers \Wikimedia\Parsoid\Utils\DOMPostOrder::traverse
	 */
	public function testTraverse() {
		$trace = [];

		$html = <<<'HTML'
<html><body>
	<div id="x1">
		<div id="x1_1"></div>
		<div id="x1_2"></div>
	</div>
	<div id="x2">
		<div id="x2_1"></div>
	</div>
</body></html>
HTML;
		$doc = DOMCompat::newDocument( true );
		$doc->loadHTML( $html );

		DOMPostOrder::traverse( $doc->documentElement, static function ( Node $node ) use ( &$trace ) {
			if ( $node instanceof Element && $node->hasAttribute( 'id' ) ) {
				$trace[] = DOMCompat::getAttribute( $node, 'id' );
			}
		} );

		$this->assertSame( [ 'x1_1', 'x1_2', 'x1', 'x2_1', 'x2' ], $trace );
	}

}

<?php

namespace Test\Parsoid\Ext\Pre;

use PHPUnit\Framework\TestCase;
use Wikimedia\Parsoid\Ext\ParsoidExtensionAPI;
use Wikimedia\Parsoid\Ext\Pre\Pre;
use Wikimedia\Parsoid\Mocks\MockEnv;
use Wikimedia\Parsoid\Utils\DOMCompat;

class PreTest extends TestCase {

	/**
	 * @covers \Wikimedia\Parsoid\Ext\Pre\Pre::sourceToDom
	 */
	public function testSourceToDOM() {
		$pre = new Pre();
		$env = new MockEnv( [] );
		$extApi = new ParsoidExtensionAPI( $env );
		$doc = $pre->sourceToDom( $extApi, 'abcd', [] );
		$pre = DOMCompat::querySelector( $doc, 'pre' );
		$this->assertNotNull( $pre );
		$this->assertSame( 'abcd', $pre->textContent );
	}

}

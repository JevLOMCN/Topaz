<?php

namespace Test\Parsoid\Ext\Nowiki;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\Ext\Nowiki\Nowiki;
use Wikimedia\Parsoid\Ext\ParsoidExtensionAPI;
use Wikimedia\Parsoid\Html2Wt\SerializerState;
use Wikimedia\Parsoid\Html2Wt\WikitextSerializer;
use Wikimedia\Parsoid\Mocks\MockEnv;
use Wikimedia\Parsoid\Utils\DOMCompat;

class NowikiTest extends TestCase {

	/**
	 * Create a DOM document with the given HTML body and return the given node within it.
	 * @param string $html
	 * @param string $selector
	 * @return Element
	 */
	private function getNode( string $html = '<div id="main"></div>', string $selector = '#main' ): Element {
		$document = DOMCompat::newDocument( true );
		$document->loadHTML( "<html><body>$html</body></html>" );
		return DOMCompat::querySelector( $document, $selector );
	}

	/**
	 * @return SerializerState|MockObject
	 */
	private function getState() {
		$env = new MockEnv( [] );
		$serializer = $this->getMockBuilder( WikitextSerializer::class )
			->disableOriginalConstructor()
			->getMock();
		$serializer->env = $env;
		'@phan-var WikitextSerializer|MockObject $serializer';
		/** @var WikitextSerializer|MockObject $serializer */
		$state = $this->getMockBuilder( SerializerState::class )
			->disableOriginalConstructor()
			->getMock();
		'@phan-var SerializerState|MockObject $state'; /** @var SerializerState|MockObject $state */
		$state->serializer = $serializer;
		$state->extApi = new ParsoidExtensionAPI( $env, [ 'html2wt' => [ 'state' => $state ] ] );
		return $state;
	}

	/**
	 * @covers \Wikimedia\Parsoid\Ext\Nowiki\Nowiki::sourceToDom
	 */
	public function testToDOM() {
		$nowiki = new Nowiki();
		$env = new MockEnv( [] );
		$extApi = new ParsoidExtensionAPI( $env );
		$doc = $nowiki->sourceToDom( $extApi, 'ab[[cd]]e', [] );
		$span = DOMCompat::querySelector( $doc, 'span' );
		$this->assertNotNull( $span );
		$this->assertSame( 'mw:Nowiki', DOMCompat::getAttribute( $span, 'typeof' ) );
		$this->assertSame( 'ab[[cd]]e', $span->textContent );

		$env = new MockEnv( [] );
		$extApi = new ParsoidExtensionAPI( $env );
		$doc = $nowiki->sourceToDom( $extApi, 'foo&amp;bar', [] );
		$span = DOMCompat::querySelector( $doc, 'span' );
		$this->assertNotNull( $span );
		$span2 = DOMCompat::querySelector( $span, 'span' );
		$this->assertNotNull( $span2 );
		$this->assertSame( 'mw:Entity', DOMCompat::getAttribute( $span2, 'typeof' ) );
		$this->assertSame( '&', $span2->textContent );
		$this->assertSame( 'foo&bar', $span->textContent );
	}

	/**
	 * @covers \Wikimedia\Parsoid\Ext\Nowiki\Nowiki::domToWikitext
	 */
	public function testDomToWikitext() {
		$state = $this->getState();
		$state->serializer->expects( $this->never() )
			->method( 'serializeNode' );
		$nowiki = new Nowiki();
		$node = $this->getNode( '<span typeof="mw:Nowiki"></span>', 'span' );
		$wt = $nowiki->domToWikitext( $state->extApi, $node, true );
		$this->assertSame( '<nowiki/>', $wt );

		$state = $this->getState();
		$state->serializer->expects( $this->never() )
			->method( 'serializeNode' );
		$nowiki = new Nowiki();
		$node = $this->getNode( '<span typeof="mw:Nowiki">xxx</span>', 'span' );
		$wt = $nowiki->domToWikitext( $state->extApi, $node, true );
		$this->assertSame( '<nowiki>xxx</nowiki>', $wt );
	}

}

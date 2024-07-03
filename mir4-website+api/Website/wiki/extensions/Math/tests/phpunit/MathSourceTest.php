<?php

use MediaWiki\Extension\Math\MathConfig;
use MediaWiki\Extension\Math\MathSource;

/**
 * Test the TeX source output format.
 *
 * @covers \MediaWiki\Extension\Math\MathSource
 *
 * @license GPL-2.0-or-later
 */
class MathSourceTest extends MediaWikiIntegrationTestCase {

	/**
	 * Checks the basic functionality
	 * i.e. if the span element is generated right.
	 */
	public function testBasics() {
		$renderer = $this->getServiceContainer()
			->get( 'Math.RendererFactory' )
			->getRenderer( 'a+b', [], MathConfig::MODE_SOURCE );
		$renderer->render();
		$real = $renderer->getHtmlOutput();
		$this->assertEquals(
			'<span class="mwe-math-fallback-source-inline tex" dir="ltr">$ a+b $</span>',
			$real,
			"Rendering of a+b in plain Text mode"
		);
	}

	/**
	 * Checks if newlines are converted to spaces correctly.
	 */
	public function testNewLines() {
		$renderer = $this->getServiceContainer()
			->get( 'Math.RendererFactory' )
			->getRenderer( "a\n b", [], MathConfig::MODE_SOURCE );
		$renderer->render();
		$real = $renderer->getHtmlOutput();
		$this->assertSame(
			'<span class="mwe-math-fallback-source-inline tex" dir="ltr">$ a  b $</span>',
			$real,
			"converting newlines to spaces"
		);
	}

	public function testConstructor() {
		$renderer = new MathSource( 'a' );

		$this->assertEquals( MathConfig::MODE_SOURCE, $renderer->getMode() );
	}

	public function testRender() {
		$renderer = new MathSource( 'a+b' );

		$this->assertTrue( $renderer->render() );
		$this->assertFalse( $renderer->isChanged() );
	}
}

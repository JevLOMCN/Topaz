<?php

use MediaWiki\Extension\Math\MathLaTeXML;

/**
 * Test the LaTeXML output format.
 *
 * @covers \MediaWiki\Extension\Math\MathLaTeXML
 *
 * @group Math
 *
 * @license GPL-2.0-or-later
 */
class MathLaTeXMLTest extends MediaWikiIntegrationTestCase {

	/**
	 * Tests the serialization of the LaTeXML settings
	 * @covers \MediaWiki\Extension\Math\MathLaTeXML::serializeSettings
	 */
	public function testSerializeSettings() {
		$renderer = new MathLaTeXML();
		$sampleSettings = [
			'k1' => 'v1',
			'k2&=' => 'v2 + & *üö',
			'k3' => [
				'v3A', 'v3b'
			]
		];
		$expected = 'k1=v1&k2%26%3D=v2+%2B+%26+%2A%C3%BC%C3%B6&k3=v3A&k3=v3b';
		$this->assertEquals(
			$expected,
			$renderer->serializeSettings( $sampleSettings ),
			'test serialization of array settings'
		);
		$this->assertEquals(
			$expected,
			$renderer->serializeSettings( $expected ),
			'test serialization of a string setting'
		);
	}
}

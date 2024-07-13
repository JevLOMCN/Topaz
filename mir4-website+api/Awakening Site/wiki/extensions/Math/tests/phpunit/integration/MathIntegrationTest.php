<?php
namespace MediaWiki\Extension\Math\Tests;

use MediaWiki\Extension\Math\Math;

/**
 * @covers \MediaWiki\Extension\Math\Math
 */
class MathIntegrationTest extends \MediaWikiIntegrationTestCase {
	public function testGetMathConfigNull() {
		$config = Math::getMathConfig();
		$this->assertInstanceOf( '\MediaWiki\Extension\Math\MathConfig', $config );
	}
}

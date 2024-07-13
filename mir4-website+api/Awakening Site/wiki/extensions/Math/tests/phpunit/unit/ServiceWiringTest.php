<?php

namespace MediaWiki\Extension\Math\Tests;

use Generator;
use MediaWikiUnitTestCase;

/**
 * @coversNothing
 */
class ServiceWiringTest extends MediaWikiUnitTestCase {
	private const EXTENSION_PREFIX = 'Math.';

	/**
	 * @dataProvider provideWiring
	 */
	public function testAllWiringsAreProperlyShaped( $name, $definition ): void {
		$this->assertStringStartsWith( self::EXTENSION_PREFIX, $name );
		$this->assertIsCallable( $definition );
	}

	public static function provideWiring(): Generator {
		$wiring = require __DIR__ . '/../../../ServiceWiring.php';

		foreach ( $wiring as $name => $definition ) {
			yield $name => [ $name, $definition ];
		}
	}

}

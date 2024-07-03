<?php

/**
 * Copy of CentralAuth's CentralAuthServiceWiringTest.php
 * as it could not be included as it's in another extension.
 */

/**
 * @coversNothing
 */
class CategoryTreeServiceWiringTest extends MediaWikiIntegrationTestCase {
	/**
	 * @dataProvider provideService
	 */
	public function testService( string $name ) {
		$this->getServiceContainer()->get( $name );
		$this->addToAssertionCount( 1 );
	}

	public static function provideService() {
		$wiring = require __DIR__ . '/../../includes/ServiceWiring.php';
		foreach ( $wiring as $name => $_ ) {
			yield $name => [ $name ];
		}
	}
}

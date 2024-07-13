<?php
namespace MediaWiki\Extension\Math\Tests;

use ExtensionRegistry;
use MediaWiki\Config\HashConfig;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\Math\Math;
use MediaWiki\Extension\Math\MathConfig;
use MediaWiki\MediaWikiServices;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\Extension\Math\Math
 */
class MathTest extends MediaWikiUnitTestCase {
	private const DUMMY_URL = 'https://example.com/api.php';

	public function testGetMathConfigService() {
		$config = new HashConfig( [
			'MathDisableTexFilter' => MathConfig::NEW,
			'MathValidModes' => [ MathConfig::MODE_SOURCE ],
			'MathEntitySelectorFallbackUrl' => self::DUMMY_URL,
		] );
		$services = new MediaWikiServices( $config );
		$services->defineService( 'Math.Config',
			function ( MediaWikiServices $services ) use ( $config ) {
				return new MathConfig(
					new ServiceOptions(
						MathConfig::CONSTRUCTOR_OPTIONS,
						$config
					),
					$this->createMock( ExtensionRegistry::class )
				);
			}
		);
		$mathConfig = Math::getMathConfig( $services );
		$this->assertStringContainsString( 'new', $mathConfig->texCheckDisabled() );
	}
}

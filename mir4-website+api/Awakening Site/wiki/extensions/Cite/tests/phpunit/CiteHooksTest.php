<?php

namespace Cite\Tests;

use ApiQuerySiteinfo;
use Cite\Hooks\CiteHooks;
use MediaWiki\User\Options\StaticUserOptionsLookup;

/**
 * @covers \Cite\Hooks\CiteHooks
 * @license GPL-2.0-or-later
 */
class CiteHooksTest extends \MediaWikiIntegrationTestCase {

	public function testOnResourceLoaderGetConfigVars() {
		$vars = [];

		$config = $this->getServiceContainer()->getMainConfig();

		$citeHooks = new CiteHooks( new StaticUserOptionsLookup( [] ) );
		$citeHooks->onResourceLoaderGetConfigVars( $vars, 'vector', $config );

		$this->assertArrayHasKey( 'wgCiteVisualEditorOtherGroup', $vars );
		$this->assertArrayHasKey( 'wgCiteResponsiveReferences', $vars );
	}

	public function testOnAPIQuerySiteInfoGeneralInfo() {
		$api = $this->createMock( ApiQuerySiteinfo::class );
		$api->expects( $this->once() )
			->method( 'getConfig' )
			->willReturn( $this->getServiceContainer()->getMainConfig() );

		$data = [];

		$citeHooks = new CiteHooks( new StaticUserOptionsLookup( [] ) );
		$citeHooks->onAPIQuerySiteInfoGeneralInfo( $api, $data );

		$this->assertArrayHasKey( 'citeresponsivereferences', $data );
	}

}

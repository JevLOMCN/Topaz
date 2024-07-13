<?php

use MediaWiki\Extension\Gadgets\MediaWikiGadgetsJsonRepo;

/**
 * @covers \MediaWiki\Extension\Gadgets\MediaWikiGadgetsJsonRepo
 * @group Gadgets
 * @group Database
 */
class MediaWikiGadgetsJsonRepoTest extends MediaWikiIntegrationTestCase {

	public function testGetGadget() {
		$this->editPage( 'MediaWiki:Gadgets/test.json',
			'{"module":{"pages":["test.js"]}, "settings":{"default":true}}' );

		$services = $this->getServiceContainer();
		$repo = new MediaWikiGadgetsJsonRepo(
			$services->getConnectionProvider(),
			$services->getMainWANObjectCache(),
			$services->getRevisionLookup()
		);
		$gadget = $repo->getGadget( 'test' );
		$this->assertTrue( $gadget->isOnByDefault() );
		$this->assertArrayEquals( [ "MediaWiki:Gadget-test.js" ], $gadget->getScripts() );
	}

	public function testGetGadgetIds() {
		$this->editPage( 'MediaWiki:Gadgets/X1.json',
			'{"module":{"pages":["MediaWiki:Gadget-test.js"]}, "settings":{"default":true}}' );
		$this->editPage( 'MediaWiki:Gadgets/X2.json',
			'{"module":{"pages":["MediaWiki:Gadget-test.js"]}, "settings":{"default":true}}' );

		$services = $this->getServiceContainer();
		$dbProvider = $services->getConnectionProvider();
		$wanCache = $services->getMainWANObjectCache();
		$repo = new MediaWikiGadgetsJsonRepo( $dbProvider, $wanCache, $services->getRevisionLookup() );
		$wanCache->clearProcessCache();
		$this->assertArrayEquals( [ 'X1', 'X2' ], $repo->getGadgetIds() );
	}
}

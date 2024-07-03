<?php

use MediaWiki\Extension\Gadgets\Gadget;
use MediaWiki\Extension\Gadgets\MultiGadgetRepo;
use MediaWiki\Extension\Gadgets\StaticGadgetRepo;

/**
 * @covers \MediaWiki\Extension\Gadgets\MultiGadgetRepo
 * @group Gadgets
 * @group Database
 */
class MultiGadgetRepoTest extends MediaWikiIntegrationTestCase {
	public function testMultiGadgetRepo() {
		$repo = new MultiGadgetRepo( [
			new StaticGadgetRepo( [
				'g1' => new Gadget( [ 'name' => 'g1', 'onByDefault' => true ] ),
				'g2' => new Gadget( [ 'name' => 'g2', 'onByDefault' => true ] ),
			] ),
			new StaticGadgetRepo( [
				'g1' => new Gadget( [ 'name' => 'g1', 'onByDefault' => false ] ),
				'g3' => new Gadget( [ 'name' => 'g3', 'onByDefault' => false ] ),
			] )
		] );

		$this->assertTrue( $repo->getGadget( 'g1' )->isOnByDefault() );
		$this->assertTrue( $repo->getGadget( 'g2' )->isOnByDefault() );
		$this->assertFalse( $repo->getGadget( 'g3' )->isOnByDefault() );

		$this->assertCount( 1, $repo->validationWarnings( $repo->getGadget( 'g1' ) ) );
	}
}

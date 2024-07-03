<?php

use MediaWiki\Extension\Gadgets\Hooks as GadgetHooks;
use MediaWiki\Extension\Gadgets\StaticGadgetRepo;
use MediaWiki\Output\OutputPage;
use MediaWiki\Title\Title;

/**
 * @covers \MediaWiki\Extension\Gadgets\GadgetLoadConditions
 * @group Gadgets
 * @group Database
 */
class GadgetLoadConditionsTest extends MediaWikiIntegrationTestCase {
	use GadgetTestTrait;

	private function setupContext( array $options = [] ): IContextSource {
		$services = $this->getServiceContainer();
		RequestContext::resetMain();
		$context = RequestContext::getMain();
		$context->setTitle( Title::newFromText( $options['title'] ?? 'Main Page' ) );
		$context->setActionName( $options['action'] ?? 'view' );
		$context->getRequest()->setVal( 'withgadget', $options['withgadget'] ?? null );
		$user = $this->getTestUser()->getUser();
		$services->getUserGroupManager()->addUserToMultipleGroups( $user, $options['userGroups'] ?? [] );
		$context->setUser( $user );
		$skin = $this->createMock( Skin::class );
		$skin->method( 'getSkinName' )->willReturn( $options['skin'] ?? 'vector' );
		$context->setSkin( $skin );
		return $context;
	}

	private function outputBeforePageDisplay( IContextSource $context, array $definitions ): OutputPage {
		$services = $this->getServiceContainer();

		$gadgets = [];
		foreach ( $definitions as $definition ) {
			$g = $this->makeGadget( $definition );
			$gadgets[ $g->getName() ] = $g;
		}
		$repo = new StaticGadgetRepo( $gadgets );

		$out = new OutputPage( $context );
		$hooks = new GadgetHooks( $repo, $services->getUserOptionsLookup(), null );
		$hooks->onBeforePageDisplay( $out, $context->getSkin() );

		return $out;
	}

	private function getLoadedModules( IContextSource $context, array $definitions ): array {
		return $this->outputBeforePageDisplay( $context, $definitions )->getModules();
	}

	public function testLoadByUrl() {
		$defs = [
			"*g1 [ResourceLoader]|test.js",
			"*g2 [ResourceLoader|supportsUrlLoad]|test.js",
		];
		$this->assertArrayEquals( [], $this->getLoadedModules( $this->setupContext(), $defs ) );

		$this->assertArrayEquals( [ 'ext.gadget.g2' ],
			$this->getLoadedModules( $this->setupContext( [ 'withgadget' => 'g2' ] ), $defs ) );
	}

	public function testContentModelRestriction() {
		$defs = [
			"*g1 [ResourceLoader|default]|test.js",
			"*g2 [ResourceLoader|default|contentModels=javascript]|test.js",
			"*g3 [ResourceLoader|default|contentModels=wikitext]|test.js"
		];
		$this->assertArrayEquals( [ 'ext.gadget.g1', 'ext.gadget.g2' ],
			$this->getLoadedModules( $this->setupContext( [ 'title' => 'MediaWiki:Common.js' ] ), $defs ) );

		$this->assertArrayEquals( [ 'ext.gadget.g1', 'ext.gadget.g3' ],
			$this->getLoadedModules( $this->setupContext(), $defs ) );
	}

	public function testNamespaceRestriction() {
		$defs = [
			"*g1 [ResourceLoader|default]|test.js",
			"*g2 [ResourceLoader|default|namespaces=0]|test.js",
			"*g3 [ResourceLoader|default|namespaces=1]|test.js"
		];
		$this->assertArrayEquals( [ 'ext.gadget.g1', 'ext.gadget.g2' ],
			$this->getLoadedModules( $this->setupContext(), $defs ) );

		$this->assertArrayEquals( [ 'ext.gadget.g1', 'ext.gadget.g3' ],
			$this->getLoadedModules( $this->setupContext( [ 'title' => 'Talk:Main Page' ] ), $defs ) );
	}

	public function testSkinRestriction() {
		$defs = [
			"*g1 [ResourceLoader|default]|test.js",
			"*g2 [ResourceLoader|default|skins=vector]|test.js",
			"*g3 [ResourceLoader|default|skins=minerva]|test.js"
		];
		$this->assertArrayEquals( [ 'ext.gadget.g1', 'ext.gadget.g2' ],
			$this->getLoadedModules( $this->setupContext( [ 'skin' => 'vector' ] ), $defs ) );

		$this->assertArrayEquals( [ 'ext.gadget.g1', 'ext.gadget.g3' ],
			$this->getLoadedModules( $this->setupContext( [ 'skin' => 'minerva' ] ), $defs ) );
	}

	public function testActionRestriction() {
		$defs = [
			"*g1 [ResourceLoader|default]|test.js",
			"*g2 [ResourceLoader|default|actions=view]|test.js",
			"*g3 [ResourceLoader|default|actions=edit]|test.js"
		];
		$this->assertArrayEquals( [ 'ext.gadget.g1', 'ext.gadget.g2' ],
			$this->getLoadedModules( $this->setupContext( [ 'action' => 'view' ] ), $defs ) );

		$this->assertArrayEquals( [ 'ext.gadget.g1', 'ext.gadget.g3' ],
			$this->getLoadedModules( $this->setupContext( [ 'action' => 'edit' ] ), $defs ) );
	}

	public function testRightRestriction() {
		$defs = [
			"*g1 [ResourceLoader|default]|test.js",
			"*g2 [ResourceLoader|default|rights=read]|test.js",
			"*g3 [ResourceLoader|default|rights=delete]|test.js"
		];
		$this->assertArrayEquals( [ 'ext.gadget.g1', 'ext.gadget.g2' ],
			$this->getLoadedModules( $this->setupContext( [ 'userGroups' => [] ] ), $defs ) );

		$this->assertArrayEquals( [ 'ext.gadget.g1', 'ext.gadget.g2', 'ext.gadget.g3' ],
			$this->getLoadedModules( $this->setupContext( [ 'userGroups' => [ 'sysop' ] ] ), $defs ) );
	}

}

<?php

use MediaWiki\Extension\Gadgets\Gadget;
use MediaWiki\Extension\Gadgets\Hooks as GadgetHooks;
use MediaWiki\Extension\Gadgets\StaticGadgetRepo;
use MediaWiki\Output\OutputPage;
use MediaWiki\Title\Title;

/**
 * @covers \MediaWiki\Extension\Gadgets\GadgetLoadConditions
 * @covers \MediaWiki\Extension\Gadgets\Hooks
 * @group Gadgets
 * @group Database
 */
class GadgetHooksTest extends MediaWikiIntegrationTestCase {

	public function testDefaultGadget() {
		$services = $this->getServiceContainer();
		$repo = new StaticGadgetRepo( [
			'g1' => new Gadget( [ 'name' => 'g1', 'onByDefault' => true, 'pages' => [ 'test.css' ] ] ),
		] );
		$hooks = new GadgetHooks( $repo, $services->getUserOptionsLookup(), null );
		$out = new OutputPage( RequestContext::getMain() );
		$out->setTitle( Title::newMainPage() );
		$skin = $this->createMock( Skin::class );
		$hooks->onBeforePageDisplay( $out, $skin );
		$this->assertArrayEquals( [ 'ext.gadget.g1' ], $out->getModuleStyles() );
	}

	public function testEnabledGadget() {
		$services = $this->getServiceContainer();
		$repo = new StaticGadgetRepo( [
			'g1' => new Gadget( [ 'name' => 'g1', 'pages' => [ 'test.js' ], 'resourceLoaded' => true ] ),
		] );
		$hooks = new GadgetHooks( $repo, $services->getUserOptionsLookup(), null );
		$context = RequestContext::getMain();
		$out = new OutputPage( $context );
		$out->setTitle( Title::newMainPage() );
		$user = $this->getTestUser()->getUser();
		$context->setUser( $user );
		$skin = $this->createMock( Skin::class );

		$hooks->onBeforePageDisplay( $out, $skin );
		$this->assertArrayEquals( [], $out->getModules() );

		$services->getUserOptionsManager()->setOption( $user, 'gadget-g1', true );
		$services->getUserOptionsManager()->saveOptions( $user );
		$hooks->onBeforePageDisplay( $out, $skin );
		$this->assertArrayEquals( [ 'ext.gadget.g1' ], $out->getModules() );
	}

	public function testDefaultUserOptions() {
		$repo = new StaticGadgetRepo( [
			'g1' => new Gadget( [ 'name' => 'g1', 'pages' => [ 'test.css' ], 'onByDefault' => true ] ),
			'g2' => new Gadget( [ 'name' => 'g2', 'pages' => [ 'test.css' ] ] ),
			'g3' => new Gadget( [ 'name' => 'g3', 'pages' => [ 'test.css' ], 'hidden' => true ] ),
		] );
		$this->setService( 'GadgetsRepo', $repo );
		$optionsLookup = $this->getServiceContainer()->getUserOptionsLookup();
		$user = $this->getTestUser()->getUser();
		$this->assertSame( 1, $optionsLookup->getOption( $user, 'gadget-g1' ) );
		$this->assertSame( 0, $optionsLookup->getOption( $user, 'gadget-g2' ) );
		$this->assertNull( $optionsLookup->getOption( $user, 'gadget-g3' ) );
	}

	public function testPreferences() {
		$services = $this->getServiceContainer();
		$repo = new StaticGadgetRepo( [
			'foo' => new Gadget( [ 'name' => 'foo', 'pages' => [ 'foo.css' ] ] ),
			'bar' => new Gadget( [ 'name' => 'bar', 'pages' => [ 'bar.css' ],
				'category' => 'keep-section1' ] ),
			'baz' => new Gadget( [ 'name' => 'baz', 'pages' => [ 'baz.css' ], 'requiredRights' => [ 'delete' ],
				'category' => 'remove-section' ] ),
			'quux' => new Gadget( [ 'name' => 'quux', 'pages' => [ 'quux.css' ], 'requiredRights' => [ 'read' ],
				'category' => 'keep-section2' ] ),
		] );
		$hooks = new GadgetHooks( $repo, $services->getUserOptionsLookup(), null );

		$user = $this->getTestUser()->getUser();
		$hooks->onGetPreferences( $user, $prefs );

		// Type is 'check' for visible preferences, 'api' for invisible ones
		$this->assertEquals( 'check', $prefs['gadget-bar']['type'] );
		$this->assertEquals( 'api', $prefs['gadget-baz']['type'],
			'Must not show unavailable gadgets' );
		$this->assertEquals( 'gadgets/gadget-section-keep-section2', $prefs['gadget-quux']['section'] );

		$services->getUserGroupManager()->addUserToGroup( $user, 'sysop' );
		$hooks->onGetPreferences( $user, $prefs );
		$this->assertEquals( 'check', $prefs['gadget-bar']['type'] );
		// Now that the user is a sysop, option should be visible
		$this->assertEquals( 'check', $prefs['gadget-baz']['type'] );
	}
}

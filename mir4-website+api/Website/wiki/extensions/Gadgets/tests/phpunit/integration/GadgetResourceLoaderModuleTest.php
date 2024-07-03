<?php

use MediaWiki\Extension\Gadgets\Gadget;
use MediaWiki\Extension\Gadgets\GadgetResourceLoaderModule;
use MediaWiki\Extension\Gadgets\StaticGadgetRepo;
use MediaWiki\MainConfigNames;
use MediaWiki\ResourceLoader as RL;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \MediaWiki\Extension\Gadgets\GadgetResourceLoaderModule
 * @group Gadgets
 * @group Database
 */
class GadgetResourceLoaderModuleTest extends MediaWikiIntegrationTestCase {
	use GadgetTestTrait;

	/** @var Gadget */
	private $gadget;
	/** @var TestingAccessWrapper */
	private $gadgetModule;

	protected function setUp(): void {
		parent::setUp();
		$this->gadget = $this->makeGadget( '*foo [ResourceLoader|package]|foo.js|foo.css|foo.json' );
		$this->gadgetModule = $this->makeGadgetModule( $this->gadget );
		$this->overrideConfigValue( MainConfigNames::ResourceLoaderValidateJS, true );
	}

	public function testGetPages() {
		$context = $this->createMock( RL\Context::class );
		$pages = $this->gadgetModule->getPages( $context );
		$this->assertArrayHasKey( 'MediaWiki:Gadget-foo.css', $pages );
		$this->assertArrayHasKey( 'MediaWiki:Gadget-foo.js', $pages );
		$this->assertArrayHasKey( 'MediaWiki:Gadget-foo.json', $pages );
		$this->assertArrayEquals( $pages, [
			[ 'type' => 'style' ],
			[ 'type' => 'script' ],
			[ 'type' => 'data' ]
		] );

		$nonPackageGadget = $this->makeGadget( '*foo [ResourceLoader]|foo.js|foo.css|foo.json' );
		$nonPackageGadgetModule = $this->makeGadgetModule( $nonPackageGadget );
		$this->assertArrayNotHasKey( 'MediaWiki:Gadget-foo.json',
			$nonPackageGadgetModule->getPages( $context ) );
	}

	public static function provideValidateScript() {
		yield 'valid ES5' => [ true, '[ResourceLoader]', 'var quux = function() {};' ];
		yield 'valid ES6' => [ true, '[ResourceLoader]', 'let quux = (() => {})();' ];
		yield 'invalid' => [ false, '[ResourceLoader]', 'boom quux = <3;' ];

		yield 'requiresES6 allows ES5' => [ true, '[ResourceLoader|requiresES6]', 'var quux = function() {};' ];
		yield 'requiresES6 allows ES6' => [ true, '[ResourceLoader|requiresES6]', 'let quux = (() => {})();' ];
		yield 'requiresES6 allows invalid' => [ true, '[ResourceLoader|requiresES6]', 'boom quux = <3;' ];
	}

	/**
	 * @dataProvider provideValidateScript
	 */
	public function testValidateScriptFile( $valid, $options, $content ) {
		$this->editPage( 'MediaWiki:Gadget-foo.js', $content );
		$repo = new StaticGadgetRepo( [
			'example' => $this->makeGadget( "* example $options | foo.js" ),
		] );
		$this->setService( 'GadgetsRepo', $repo );
		$rlContext = RL\Context::newDummyContext();

		$module = new GadgetResourceLoaderModule( [ 'id' => 'example' ] );
		$module->setConfig( $this->getServiceContainer()->getMainConfig() );
		$actual = $module->getScript( $rlContext );

		if ( !$valid ) {
			$this->assertStringContainsString( 'mw.log.error', $actual );
		} else {
			$this->assertStringContainsString( $content, $actual );
			$this->assertStringNotContainsString( 'mw.log.error', $actual );
		}
	}
}

<?php

use MediaWiki\Extension\Gadgets\Gadget;
use MediaWiki\ResourceLoader\Module;
use MediaWiki\User\User;

/**
 * @covers \MediaWiki\Extension\Gadgets\Gadget
 * @covers \MediaWiki\Extension\Gadgets\GadgetResourceLoaderModule
 * @covers \MediaWiki\Extension\Gadgets\MediaWikiGadgetsDefinitionRepo
 * @group Gadgets
 */
class GadgetTest extends MediaWikiUnitTestCase {
	use GadgetTestTrait;

	public function testToArray() {
		$g = $this->makeGadget( '*bar[ResourceLoader|rights=test]|bar.js|foo.css | foo.json' );
		$gNewFromSerialized = new Gadget( $g->toArray() );
		$this->assertArrayEquals( $g->toArray(), $gNewFromSerialized->toArray() );
	}

	public function testGadgetFromDefinitionContent() {
		$gArray = Gadget::serializeDefinition( 'bar', [
			'settings' => [
				'rights' => [],
				'default' => true,
				'package' => true,
				'hidden' => false,
				'actions' => [],
				'skins' => [],
				'namespaces' => [],
				'categories' => [],
				'contentModels' => [],
				'category' => 'misc',
				'supportsUrlLoad' => false,
				'requiresES6' => false,
			],
			'module' => [
				'pages' => [ 'foo.js', 'bar.css' ],
				'datas' => [],
				'dependencies' => [ 'moment' ],
				'peers' => [],
				'messages' => [ 'blanknamespace' ],
				'type' => 'general',
			]
		] );

		$g = new Gadget( $gArray );

		$this->assertTrue( $g->isOnByDefault() );
		$this->assertTrue( $g->isPackaged() );
		$this->assertFalse( $g->isHidden() );
		$this->assertFalse( $g->supportsUrlLoad() );
		$this->assertTrue( $g->supportsResourceLoader() );
		$this->assertCount( 1, $g->getScripts() );
		$this->assertCount( 1, $g->getStyles() );
		$this->assertCount( 0, $g->getJSONs() );
		$this->assertCount( 1, $g->getDependencies() );
		$this->assertCount( 1, $g->getMessages() );

		// Ensure parity and internal consistency
		// between Gadget::serializeDefinition and Gadget::toArray
		$arr = $g->toArray();
		unset( $arr['definition'] );
		$this->assertSame( $arr, $gArray );
	}

	public function testInvalidLines() {
		$this->assertFalse( $this->makeGadget( '' ) );
		$this->assertFalse( $this->makeGadget( '<foo|bar>' ) );
	}

	public function testSimpleCases() {
		$g = $this->makeGadget( '* foo bar| foo.css|foo.js|foo.json|foo.bar' );
		$this->assertEquals( 'foo_bar', $g->getName() );
		$this->assertEquals( 'ext.gadget.foo_bar', Gadget::getModuleName( $g->getName() ) );
		$this->assertEquals( [ 'MediaWiki:Gadget-foo.js' ], $g->getScripts() );
		$this->assertEquals( [ 'MediaWiki:Gadget-foo.css' ], $g->getStyles() );
		$this->assertEquals( [ 'MediaWiki:Gadget-foo.json' ], $g->getJSONs() );
		$this->assertEquals( [ 'MediaWiki:Gadget-foo.js', 'MediaWiki:Gadget-foo.css', 'MediaWiki:Gadget-foo.json' ],
			$g->getScriptsAndStyles() );
		$this->assertEquals( [ 'MediaWiki:Gadget-foo.js' ], $g->getLegacyScripts() );
		$this->assertFalse( $g->supportsResourceLoader() );
		$this->assertTrue( $g->hasModule() );
	}

	public function testRLtag() {
		$g = $this->makeGadget( '*foo [ResourceLoader]|foo.js|foo.css' );
		$this->assertEquals( 'foo', $g->getName() );
		$this->assertTrue( $g->supportsResourceLoader() );
		$this->assertCount( 0, $g->getLegacyScripts() );
	}

	public function testPackaged() {
		$g = $this->makeGadget( '* foo bar[ResourceLoader|package]| foo.css|foo.js|foo.bar|foo.json' );
		$this->assertEquals( 'foo_bar', $g->getName() );
		$this->assertEquals( 'ext.gadget.foo_bar', Gadget::getModuleName( $g->getName() ) );
		$this->assertEquals( [ 'MediaWiki:Gadget-foo.js' ], $g->getScripts() );
		$this->assertEquals( [ 'MediaWiki:Gadget-foo.css' ], $g->getStyles() );
		$this->assertEquals( [ 'MediaWiki:Gadget-foo.json' ], $g->getJSONs() );
		$this->assertEquals( [ 'MediaWiki:Gadget-foo.js', 'MediaWiki:Gadget-foo.css', 'MediaWiki:Gadget-foo.json' ],
			$g->getScriptsAndStyles() );
		$this->assertEquals( [], $g->getLegacyScripts() );
		$this->assertTrue( $g->supportsResourceLoader() );
		$this->assertTrue( $g->hasModule() );
	}

	public function testSupportsUrlLoad() {
		$directLoadAllowedByDefault = $this->makeGadget( '*foo[ResourceLoader]|foo.js' );
		$directLoadAllowed1 = $this->makeGadget( '*bar[ResourceLoader|supportsUrlLoad]|bar.js' );
		$directLoadAllowed2 = $this->makeGadget( '*bar[ResourceLoader|supportsUrlLoad=true]|bar.js' );
		$directLoadNotAllowed = $this->makeGadget( '*baz[ResourceLoader|supportsUrlLoad=false]|baz.js' );

		$this->assertFalse( $directLoadAllowedByDefault->supportsUrlLoad() );
		$this->assertTrue( $directLoadAllowed1->supportsUrlLoad() );
		$this->assertTrue( $directLoadAllowed2->supportsUrlLoad() );
		$this->assertFalse( $directLoadNotAllowed->supportsUrlLoad() );
	}

	public function testIsAllowed() {
		$user = $this->getMockBuilder( User::class )
			->onlyMethods( [ 'isAllowedAll' ] )
			->getMock();
		$user->method( 'isAllowedAll' )
			->willReturnCallback(
				static function ( ...$rights ) {
					return array_diff( $rights, [ 'test' ] ) === [];
				}
			);

		/** @var User $user */
		$gUnset = $this->makeGadget( '*foo[ResourceLoader]|foo.js' );
		$gAllowed = $this->makeGadget( '*bar[ResourceLoader|rights=test]|bar.js' );
		$gNotAllowed = $this->makeGadget( '*baz[ResourceLoader|rights=nope]|baz.js' );
		$this->assertTrue( $gUnset->isAllowed( $user ) );
		$this->assertTrue( $gAllowed->isAllowed( $user ) );
		$this->assertFalse( $gNotAllowed->isAllowed( $user ) );
	}

	public function testSkinsTag() {
		$gUnset = $this->makeGadget( '*foo[ResourceLoader]|foo.js' );
		$gSkinSupported = $this->makeGadget( '*bar[ResourceLoader|skins=fallback]|bar.js' );
		$gSkinNotSupported = $this->makeGadget( '*baz[ResourceLoader|skins=bar]|baz.js' );
		$skin = new SkinFallback();
		$this->assertTrue( $gUnset->isSkinSupported( $skin ) );
		$this->assertTrue( $gSkinSupported->isSkinSupported( $skin ) );
		$this->assertFalse( $gSkinNotSupported->isSkinSupported( $skin ) );
	}

	public function testActionsTag() {
		$gUnset = $this->makeGadget( '*foo[ResourceLoader]|foo.js' );
		$gActionSupported = $this->makeGadget( '*bar[ResourceLoader|actions=edit]|bar.js' );
		$gActionNotSupported = $this->makeGadget( '*baz[ResourceLoader|actions=history]|baz.js' );
		$this->assertTrue( $gUnset->isActionSupported( 'edit' ) );
		$this->assertTrue( $gActionSupported->isActionSupported( 'edit' ) );
		$this->assertFalse( $gActionNotSupported->isActionSupported( 'edit' ) );

		// special case
		$this->assertTrue( $gActionSupported->isActionSupported( 'submit' ) );

		$gMultiActions = $this->makeGadget( '*bar[ResourceLoader|actions=unknown,history]|bar.js' );
		$this->assertTrue( $gMultiActions->isActionSupported( 'history' ) );
		$this->assertFalse( $gMultiActions->isActionSupported( 'view' ) );
	}

	public function testNamespacesTag() {
		$gUnsetNamespace = $this->makeGadget( '*foo[ResourceLoader]|foo.js' );
		$gNamespace0 = $this->makeGadget( '*bar[ResourceLoader|namespaces=0]|bar.js' );
		$gNamespace1 = $this->makeGadget( '*bar[ResourceLoader|namespaces=1]|bar.js' );
		$gMultiNamespace = $this->makeGadget( '*bar[ResourceLoader|namespaces=1,2,3,4]|bar.js' );

		$this->assertTrue( $gUnsetNamespace->isNamespaceSupported( 5 ) );

		$this->assertTrue( $gNamespace0->isNamespaceSupported( 0 ) );
		$this->assertFalse( $gNamespace0->isNamespaceSupported( 2 ) );

		$this->assertTrue( $gNamespace1->isNamespaceSupported( 1 ) );
		$this->assertFalse( $gNamespace1->isNamespaceSupported( 2 ) );

		$this->assertTrue( $gMultiNamespace->isNamespaceSupported( 1 ) );
		$this->assertTrue( $gMultiNamespace->isNamespaceSupported( 2 ) );
		$this->assertFalse( $gMultiNamespace->isNamespaceSupported( 5 ) );

		$this->assertSame( [ '1', '2', '3', '4' ], $gMultiNamespace->getRequiredNamespaces() );
	}

	public function testCategoriesTag() {
		$gUnsetCategory = $this->makeGadget( '*foo[ResourceLoader]|foo.js' );
		$gCategoryFoo = $this->makeGadget( '*foo[ResourceLoader|categories=Foo]|foo.js' );
		$gMultiCategory = $this->makeGadget( '*foo[ResourceLoader|categories=Foo,Bar baz,quux]|foo.js' );

		$this->assertTrue( $gUnsetCategory->isCategorySupported( [ 'Foo' ] ) );

		$this->assertTrue( $gCategoryFoo->isCategorySupported( [ 'Foo' ] ) );
		$this->assertFalse( $gCategoryFoo->isCategorySupported( [ 'Bar' ] ) );
		$this->assertFalse( $gCategoryFoo->isCategorySupported( [ 'Bar baz' ] ) );

		$this->assertTrue( $gMultiCategory->isCategorySupported( [ 'Foo' ] ) );
		$this->assertFalse( $gMultiCategory->isCategorySupported( [ 'Bar' ] ) );
		$this->assertTrue( $gMultiCategory->isCategorySupported( [ 'Spam', 'Bar baz', 'Eggs' ] ) );
		$this->assertFalse( $gMultiCategory->isCategorySupported( [ 'Spam', 'Eggs' ] ) );
		// Load condition must use title text form
		$this->assertFalse( $gMultiCategory->isCategorySupported( [ 'foo' ] ) );
		$this->assertFalse( $gMultiCategory->isCategorySupported( [ 'Bar_baz' ] ) );
		// Definition must use title text form, too
		$this->assertFalse( $gMultiCategory->isCategorySupported( [ 'Quux' ] ) );
	}

	public function testContentModelsTags() {
		$gUnsetModel = $this->makeGadget( '*foo[ResourceLoader]|foo.js' );
		$gModelWikitext = $this->makeGadget( '*bar[ResourceLoader|contentModels=wikitext]|bar.js' );
		$gModelCode = $this->makeGadget( '*bar[ResourceLoader|contentModels=javascript,css]|bar.js' );

		$this->assertTrue( $gUnsetModel->isContentModelSupported( 'wikitext' ) );

		$this->assertTrue( $gModelWikitext->isContentModelSupported( 'wikitext' ) );
		$this->assertFalse( $gModelWikitext->isContentModelSupported( 'javascript' ) );

		$this->assertTrue( $gModelCode->isContentModelSupported( 'javascript' ) );
		$this->assertTrue( $gModelCode->isContentModelSupported( 'css' ) );
		$this->assertFalse( $gModelCode->isContentModelSupported( 'wikitext' ) );
	}

	public function testDependencies() {
		$g = $this->makeGadget( '* foo[ResourceLoader|dependencies=jquery.ui]|bar.js' );
		$this->assertEquals( [ 'MediaWiki:Gadget-bar.js' ], $g->getScripts() );
		$this->assertTrue( $g->supportsResourceLoader() );
		$this->assertEquals( [ 'jquery.ui' ], $g->getDependencies() );
	}

	public function testES6() {
		$es6gadget = $this->makeGadget( '* foo[ResourceLoader|requiresES6]|bar.js' );
		$es5gadget = $this->makeGadget( '* foo[ResourceLoader]|bar.js' );
		$this->assertTrue( $es6gadget->requiresES6() );
		$this->assertFalse( $es5gadget->requiresES6() );
	}

	public static function provideGetType() {
		return [
			[
				'Default (mixed)',
				'* foo[ResourceLoader]|bar.css|bar.js',
				'general',
				Module::LOAD_GENERAL,
			],
			[
				'Default (styles only)',
				'* foo[ResourceLoader]|bar.css',
				'styles',
				Module::LOAD_STYLES,
			],
			[
				'Default (scripts only)',
				'* foo[ResourceLoader]|bar.js',
				'general',
				Module::LOAD_GENERAL,
			],
			[
				'Default (styles only with dependencies)',
				'* foo[ResourceLoader|dependencies=jquery.ui]|bar.css',
				'general',
				Module::LOAD_GENERAL,
			],
			[
				'Styles type (mixed)',
				'* foo[ResourceLoader|type=styles]|bar.css|bar.js',
				'styles',
				Module::LOAD_STYLES,
			],
			[
				'Styles type (styles only)',
				'* foo[ResourceLoader|type=styles]|bar.css',
				'styles',
				Module::LOAD_STYLES,
			],
			[
				'Styles type (scripts only)',
				'* foo[ResourceLoader|type=styles]|bar.js',
				'styles',
				Module::LOAD_STYLES,
			],
			[
				'General type (mixed)',
				'* foo[ResourceLoader|type=general]|bar.css|bar.js',
				'general',
				Module::LOAD_GENERAL,
			],
			[
				'General type (styles only)',
				'* foo[ResourceLoader|type=general]|bar.css',
				'general',
				Module::LOAD_GENERAL,
			],
			[
				'General type (scripts only)',
				'* foo[ResourceLoader|type=general]|bar.js',
				'general',
				Module::LOAD_GENERAL,
			],
		];
	}

	/**
	 * @dataProvider provideGetType
	 */
	public function testType( $message, $definition, $gType, $mType ) {
		$g = $this->makeGadget( $definition );
		$this->assertEquals( $gType, $g->getType(), "Gadget: $message" );
		$this->assertEquals( $mType, $this->makeGadgetModule( $g )->getType(), "Module: $message" );
	}

	public function testIsHidden() {
		$g = $this->makeGadget( '* foo[hidden]|bar.js' );
		$this->assertTrue( $g->isHidden() );

		$g = $this->makeGadget( '* foo[ResourceLoader|hidden]|bar.js' );
		$this->assertTrue( $g->isHidden() );

		$g = $this->makeGadget( '* foo[ResourceLoader]|bar.js' );
		$this->assertFalse( $g->isHidden() );
	}

	public static function provideWarnings() {
		return [
			[
				'* foo[ResourceLoader|package]|foo.css',
				[ 'gadgets-validate-noentrypoint' ]
			],
			[
				'* foo[ResourceLoader]|foo.js,foo.json',
				[ 'gadgets-validate-json' ]
			],
			[
				'* foo[ResourceLoader|type=styles]|foo.js|foo.css',
				[ 'gadgets-validate-scriptsnotallowed' ]
			],
			[
				'* foo[ResourceLoader|type=styles|peers=bar]|foo.js|foo.json|foo.css',
				[ 'gadgets-validate-scriptsnotallowed', 'gadgets-validate-stylepeers', 'gadgets-validate-json' ]
			]
		];
	}

	/**
	 * @dataProvider provideWarnings
	 */
	public function testGadgetWarnings( $definition, $expectedMsgKeys ) {
		$g = $this->makeGadget( $definition );
		$msgKeys = $g->getValidationWarnings();
		$this->assertArrayEquals( $expectedMsgKeys, $msgKeys );
	}
}

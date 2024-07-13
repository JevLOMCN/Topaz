<?php

namespace MediaWiki\Extension\Scribunto\Tests\Engines\LuaCommon;

use MediaWiki\Interwiki\ClassicInterwikiLookup;
use MediaWiki\Page\PageIdentityValue;
use MediaWiki\Parser\ParserOutputFlags;
use MediaWiki\Permissions\RestrictionStore;
use MediaWiki\Title\Title;
use WikitextContent;

/**
 * @covers \MediaWiki\Extension\Scribunto\Engines\LuaCommon\TitleLibrary
 * @group Database
 */
class TitleLibraryTest extends LuaEngineTestBase {
	/** @inheritDoc */
	protected static $moduleName = 'TitleLibraryTests';

	/** @var Title|null */
	private $testTitle = null;

	/** @var int */
	private $testPageId = null;

	protected function setUp(): void {
		$this->setTestTitle( null );
		parent::setUp();

		// Set up interwikis (via wgInterwikiCache) before creating any Titles
		$this->overrideConfigValues( [
			'Server' => '//wiki.local',
			'CanonicalServer' => 'http://wiki.local',
			'UsePathInfo' => true,
			'ActionPaths' => [],
			'Script' => '/w/index.php',
			'ScriptPath' => '/w',
			'ArticlePath' => '/wiki/$1',
			'InterwikiCache' => ClassicInterwikiLookup::buildCdbHash( [
				[
					'iw_prefix' => 'interwikiprefix',
					'iw_url'    => '//test.wikipedia.org/wiki/$1',
					'iw_local'  => 0,
				],
			] ),
		] );

		$editor = self::getTestSysop()->getUser();

		$wikiPageFactory = $this->getServiceContainer()->getWikiPageFactory();

		// Page for getContent test
		$page = $wikiPageFactory->newFromTitle( Title::newFromText( 'ScribuntoTestPage' ) );
		$page->doUserEditContent(
			new WikitextContent(
				'{{int:mainpage}}<includeonly>...</includeonly><noinclude>...</noinclude>'
			),
			$editor,
			'Summary'
		);
		$this->testPageId = $page->getId();

		// Pages for redirectTarget tests
		$page = $wikiPageFactory->newFromTitle( Title::newFromText( 'ScribuntoTestRedirect' ) );
		$page->doUserEditContent(
			new WikitextContent( '#REDIRECT [[ScribuntoTestTarget]]' ),
			$editor,
			'Summary'
		);
		$page = $wikiPageFactory->newFromTitle( Title::newFromText( 'ScribuntoTestNonRedirect' ) );
		$page->doUserEditContent(
			new WikitextContent( 'Not a redirect.' ),
			$editor,
			'Summary'
		);

		// Set restrictions for protectionLevels and cascadingProtection tests

		$restrictionStore = $this->createNoOpMock(
			RestrictionStore::class,
			[
				'getCascadeProtectionSources',
				'getRestrictions',
				'areRestrictionsLoaded',
				'areCascadeProtectionSourcesLoaded',
				'getAllRestrictions',
				// just do nothing
				'registerOldRestrictions'
			]
		);

		$this->setService( 'RestrictionStore', $restrictionStore );

		$restrictionStore->method( 'areRestrictionsLoaded' )->willReturn( true );
		$restrictionStore->method( 'areCascadeProtectionSourcesLoaded' )->willReturn( true );

		$restrictions = [
			'Main_Page' => [ 'edit' => [], 'move' => [] ],
			'Module:TestFramework' => [
				'edit' => [ 'sysop', 'bogus' ],
				'move' => [ 'sysop', 'bogus' ],
			],
			'interwikiprefix:Module:TestFramework' => [],
			'Talk:Has/A/Subpage' => [ 'create' => [ 'sysop' ] ],
			'Not/A/Subpage' => [ 'edit' => [ 'autoconfirmed' ], 'move' => [ 'sysop' ] ],
			'Module_talk:Test_Framework' => [ 'edit' => [], 'move' => [ 'sysop' ] ],
		];

		$restrictionStore->method( 'getAllRestrictions' )
			->willReturnCallback( static function ( $title ) use ( $restrictions ) {
				$key = $title->getPrefixedDBkey();
				return $restrictions[$key] ?? [];
			} );

		$restrictionStore->method( 'getRestrictions' )
			->willReturnCallback( static function ( $title, $action ) {
				$key = $title->getPrefixedDBkey();
				$pageRestrictions = $restrictions[$key] ?? [];
				return $pageRestrictions[$action] ?? [];
			} );

		$restrictionStore->method( 'getCascadeProtectionSources' )
			->willReturnCallback( static function ( $title ) {
				if ( $title->getPrefixedDBkey() === 'Main_Page' ) {
					return [
						[
							PageIdentityValue::localIdentity( 5678, NS_MAIN, "Lockbox" ),
							PageIdentityValue::localIdentity( 8765, NS_MAIN, "Lockbox2" ),
						],
						[ 'edit' => [ 'sysop' ] ]
					];
				} else {
					return [ [], [] ];
				}
			} );

		// Note this depends on every iteration of the data provider running with a clean parser
		$this->getEngine()->getParser()->getOptions()->setExpensiveParserFunctionLimit( 10 );

		// Indicate to the tests that it's safe to create the title objects
		$interpreter = $this->getEngine()->getInterpreter();
		$interpreter->callFunction(
			$interpreter->loadString( "mw.title.testPageId = $this->testPageId", 'fortest' )
		);
	}

	protected function getTestTitle() {
		return $this->testTitle ?? parent::getTestTitle();
	}

	protected function setTestTitle( $title ) {
		$this->testTitle = $title !== null ? Title::newFromText( $title ) : null;
		$this->resetEngine();
	}

	protected function getTestModules() {
		return parent::getTestModules() + [
			'TitleLibraryTests' => __DIR__ . '/TitleLibraryTests.lua',
		];
	}

	public function testAddsLinks() {
		$engine = $this->getEngine();
		$interpreter = $engine->getInterpreter();

		// Loading a title should create a link
		$links = $engine->getParser()->getOutput()->getLinks();
		$this->assertFalse( isset( $links[NS_PROJECT]['Referenced_from_Lua'] ) );

		$interpreter->callFunction( $interpreter->loadString(
			'local _ = mw.title.new( "Project:Referenced from Lua" ).id', 'reference title'
		) );

		$links = $engine->getParser()->getOutput()->getLinks();
		$this->assertArrayHasKey( NS_PROJECT, $links );
		$this->assertArrayHasKey( 'Referenced_from_Lua', $links[NS_PROJECT] );

		// Loading the page content should create a templatelink
		$templates = $engine->getParser()->getOutput()->getTemplates();
		$this->assertFalse( isset( $links[NS_PROJECT]['Loaded_from_Lua'] ) );

		$interpreter->callFunction( $interpreter->loadString(
			'mw.title.new( "Project:Loaded from Lua" ):getContent()', 'load title'
		) );

		$templates = $engine->getParser()->getOutput()->getTemplates();
		$this->assertArrayHasKey( NS_PROJECT, $templates );
		$this->assertArrayHasKey( 'Loaded_from_Lua', $templates[NS_PROJECT] );
	}

	/**
	 * @dataProvider provideVaryPageId
	 */
	public function testVaryPageId( $testTitle, $code, $flag ) {
		$this->setTestTitle( $testTitle );

		$code = strtr( $code, [ '$$ID$$' => $this->testPageId ] );

		$engine = $this->getEngine();
		$interpreter = $engine->getInterpreter();
		$this->assertFalse(
			$engine->getParser()->getOutput()->getOutputFlag( ParserOutputFlags::VARY_PAGE_ID ), 'sanity check'
		);

		$interpreter->callFunction( $interpreter->loadString(
			"local _ = $code", 'reference title but not id'
		) );
		$this->assertFalse( $engine->getParser()->getOutput()->getOutputFlag( ParserOutputFlags::VARY_PAGE_ID ) );

		$interpreter->callFunction( $interpreter->loadString(
			"local _ = $code.id", 'reference id'
		) );
		$this->assertSame( $flag, $engine->getParser()->getOutput()->getOutputFlag( ParserOutputFlags::VARY_PAGE_ID ) );
	}

	public static function provideVaryPageId() {
		return [
			'by getCurrentTitle()' => [
				'ScribuntoTestPage',
				'mw.title.getCurrentTitle()',
				true
			],
			'by name' => [
				'ScribuntoTestPage',
				'mw.title.new("ScribuntoTestPage")',
				true
			],
			'by id' => [
				'ScribuntoTestPage',
				'mw.title.new( $$ID$$ )',
				true
			],

			'other page by name' => [
				'ScribuntoTestRedirect',
				'mw.title.new("ScribuntoTestPage")',
				false
			],
			'other page by id' => [
				'ScribuntoTestRedirect',
				'mw.title.new( $$ID$$ )',
				false
			],

			'new page by getCurrentTitle()' => [
				'ScribuntoTestPage/DoesNotExist',
				'mw.title.getCurrentTitle()',
				true
			],
			'new page by name' => [
				'ScribuntoTestPage/DoesNotExist',
				'mw.title.new("ScribuntoTestPage/DoesNotExist")',
				true
			],
		];
	}
}

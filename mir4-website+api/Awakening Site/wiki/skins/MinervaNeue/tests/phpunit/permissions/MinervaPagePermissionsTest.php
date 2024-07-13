<?php

namespace MediaWiki\Minerva;

use ContentHandler;
use MediaWiki\Content\IContentHandlerFactory;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\MainConfigNames;
use MediaWiki\Minerva\Permissions\IMinervaPagePermissions;
use MediaWiki\Minerva\Permissions\MinervaPagePermissions;
use MediaWiki\Minerva\Skins\SkinUserPageHelper;
use MediaWiki\Permissions\Authority;
use MediaWiki\Tests\Unit\Permissions\MockAuthorityTrait;
use MediaWiki\Title\Title;
use MediaWiki\User\UserFactory;
use MediaWiki\Watchlist\WatchlistManager;
use MediaWikiIntegrationTestCase;
use RequestContext;

/**
 * @group MinervaNeue
 * @coversDefaultClass \MediaWiki\Minerva\Permissions\MinervaPagePermissions
 */
class MinervaPagePermissionsTest extends MediaWikiIntegrationTestCase {
	use MockAuthorityTrait;

	protected function setUp(): void {
		$this->overrideConfigValues( [
			MainConfigNames::HideInterlanguageLinks => false
		] );
	}

	private function buildPermissionsObject(
		Title $title,
		array $options = [],
		ContentHandler $contentHandler = null,
		Authority $user = null,
		$hasOtherLanguagesOrVariants = false
	) {
		$languageHelper = $this->createMock( LanguagesHelper::class );
		$languageHelper->method( 'doesTitleHasLanguagesOrVariants' )
			->willReturn( $hasOtherLanguagesOrVariants );

		$user ??= $this->mockRegisteredNullAuthority();
		$contentHandler = $contentHandler ??
			$this->getMockForAbstractClass( ContentHandler::class, [], '', false );
		$skinOptions = new SkinOptions(
			$this->createMock( HookContainer::class ),
			$this->createMock( SkinUserPageHelper::class )
		);
		if ( $options ) {
			$skinOptions->setMultiple( $options );
		}

		$context = new RequestContext();
		// Force a content model to avoid DB queries.
		$title->setContentModel( CONTENT_MODEL_WIKITEXT );
		$context->setTitle( $title );
		$context->setAuthority( $user );

		$permissionManager = $this->getServiceContainer()->getPermissionManager();

		$contentHandlerFactory = $this->createMock( IContentHandlerFactory::class );

		$contentHandlerFactory->expects( $this->once() )
			->method( 'getContentHandler' )
			->willReturn( $contentHandler );

		return ( new MinervaPagePermissions(
			$skinOptions,
			$languageHelper,
			$permissionManager,
			$contentHandlerFactory,
			$this->createMock( UserFactory::class ),
			$this->getServiceContainer()->getWatchlistManager()
		) )->setContext( $context );
	}

	/**
	 * @covers ::isAllowed
	 */
	public function testWatchAndEditNotAllowedOnMainPage() {
		$user = $this->mockAnonNullAuthority();
		$permsAnon = $this->buildPermissionsObject( Title::newMainPage(), [], null, $user );
		$perms = $this->buildPermissionsObject( Title::newMainPage() );

		$this->assertFalse( $perms->isAllowed( IMinervaPagePermissions::WATCH ) );
		$this->assertFalse( $perms->isAllowed( IMinervaPagePermissions::CONTENT_EDIT ) );
		$this->assertFalse( $permsAnon->isAllowed( IMinervaPagePermissions::TALK ) );

		// Check to make sure 'talk' and 'switch-language' are enabled on the Main page.
		$this->assertTrue( $perms->isAllowed( IMinervaPagePermissions::TALK ) );
		$this->assertTrue( $perms->isAllowed( IMinervaPagePermissions::SWITCH_LANGUAGE ) );
	}

	/**
	 * @covers ::isAllowed
	 */
	public function testInvalidPageActionsArentAllowed() {
		$perms = $this->buildPermissionsObject( Title::makeTitle( NS_MAIN, 'Test' ) );

		$this->assertFalse( $perms->isAllowed( 'blah' ) );
		$this->assertFalse( $perms->isAllowed( 'wah' ) );
	}

	/**
	 * @covers ::isAllowed
	 */
	public function testValidPageActionsAreAllowed() {
		$perms = $this->buildPermissionsObject(
			Title::makeTitle( NS_MAIN, 'Test' ),
			[],
			null,
			$this->mockRegisteredUltimateAuthority()
		);
		$this->assertTrue( $perms->isAllowed( IMinervaPagePermissions::TALK ) );
		$this->assertTrue( $perms->isAllowed( IMinervaPagePermissions::WATCH ) );
	}

	public static function editPageActionProvider() {
		return [
			[ false, false, false ],
			[ true, false, false ],
			[ true, true, true ]
		];
	}

	/**
	 * The "edit" page action is allowed when the page doesn't support direct editing via the API.
	 *
	 * @dataProvider editPageActionProvider
	 * @covers ::isAllowed
	 */
	public function testEditPageAction(
		$supportsDirectEditing,
		$supportsDirectApiEditing,
		$expected
	) {
		$contentHandler = $this->getMockBuilder( ContentHandler::class )
			->disableOriginalConstructor()
			->getMock();

		$contentHandler->method( 'supportsDirectEditing' )
			->willReturn( $supportsDirectEditing );

		$contentHandler->method( 'supportsDirectApiEditing' )
			->willReturn( $supportsDirectApiEditing );

		$perms = $this->buildPermissionsObject( Title::makeTitle( NS_MAIN, 'Test' ), [],
			$contentHandler );

		$this->assertEquals( $expected, $perms->isAllowed( IMinervaPagePermissions::CONTENT_EDIT ) );
	}

	/**
	 * @covers ::isAllowed
	 */
	public function testPageActionsWhenOnUserPage() {
		$perms = $this->buildPermissionsObject( Title::makeTitle( NS_USER, 'Admin' ) );
		$this->assertTrue( $perms->isAllowed( IMinervaPagePermissions::TALK ) );
	}

	/**
	 * @covers ::isAllowed
	 */
	public function testPageActionsWhenOnAnonUserPage() {
		$perms = $this->buildPermissionsObject( Title::makeTitle( NS_USER, '1.1.1.1' ) );
		$this->assertTrue( $perms->isAllowed( IMinervaPagePermissions::TALK ) );
	}

	public static function switchLanguagePageActionProvider() {
		return [
			[ true, false, true ],
			[ false, true, true ],
			[ false, false, false ],
		];
	}

	/**
	 * MediaWiki defines wgHideInterlanguageLinks which is default set to false, but some wikis
	 * can set this config to true. Minerva page permissions must respect that
	 * @covers ::isAllowed
	 */
	public function testGlobalHideLanguageLinksTakesPrecedenceOnMainPage() {
		$this->overrideConfigValues( [ MainConfigNames::HideInterlanguageLinks => true ] );
		$perms = $this->buildPermissionsObject( Title::newMainPage() );
		$this->assertFalse( $perms->isAllowed( IMinervaPagePermissions::SWITCH_LANGUAGE ) );
	}

	/**
	 * MediaWiki defines wgHideInterlanguageLinks which is default set to false, but some wikis
	 * can set this config to true. Minerva page permissions must respect that
	 * @covers ::isAllowed
	 */
	public function testGlobalHideLanguageLinksTakesPrecedence() {
		$this->overrideConfigValues( [ MainConfigNames::HideInterlanguageLinks => true ] );
		$perms = $this->buildPermissionsObject( Title::makeTitle( NS_MAIN, 'Test' ) );
		$this->assertFalse( $perms->isAllowed( IMinervaPagePermissions::SWITCH_LANGUAGE ) );
	}

	/**
	 * The "switch-language" page action is allowed when: v2 of the page action bar is enabled and
	 * if the page has interlanguage links or if the <code>$wgMinervaAlwaysShowLanguageButton</code>
	 * configuration variable is set to truthy.
	 *
	 * @dataProvider switchLanguagePageActionProvider
	 * @covers ::isAllowed
	 */
	public function testSwitchLanguagePageAction(
		$hasLanguagesOrVariants,
		$minervaAlwaysShowLanguageButton,
		$expected
	) {
		$title = $this->createMock( Title::class );
		$title->expects( $this->once() )
			->method( 'isMainPage' )
			->willReturn( false );
		$title->expects( $this->once() )
			->method( 'getContentModel' )
			->willReturn( CONTENT_MODEL_WIKITEXT );

		$this->overrideConfigValues( [
			'MinervaAlwaysShowLanguageButton' => $minervaAlwaysShowLanguageButton
		] );
		$permissions = $this->buildPermissionsObject(
			$title,
			[],
			null,
			null,
			$hasLanguagesOrVariants
		);

		$actual = $permissions->isAllowed( IMinervaPagePermissions::SWITCH_LANGUAGE );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Watch action requires 'viewmywatchlist' and 'editmywatchlist' permissions
	 * to be grated. Verify that isAllowedAction('watch') returns false when user
	 * do not have those permissions granted
	 * @covers ::isAllowed
	 */
	public function testWatchIsAllowedOnlyWhenWatchlistPermissionsAreGranted() {
		$title = Title::makeTitle( NS_MAIN, 'Test_watchstar_permissions' );
		$perms = $this->buildPermissionsObject( $title );
		$this->assertTrue( $perms->isAllowed( IMinervaPagePermissions::TALK ) );
		$this->assertFalse( $perms->isAllowed( IMinervaPagePermissions::WATCH ) );
	}

	/**
	 * If Title is not watchable, it cannot be watched
	 * @covers ::isAllowed
	 */
	public function testCannotWatchNotWatchableTitle() {
		$title = $this->createMock( Title::class );
		$title->expects( $this->once() )
			->method( 'isMainPage' )
			->willReturn( false );
		$title->expects( $this->once() )
			->method( 'getContentModel' )
			->willReturn( CONTENT_MODEL_UNKNOWN );

		$watchlistManager = $this->createMock( WatchlistManager::class );
		$watchlistManager->expects( $this->once() )
			->method( 'isWatchable' )
			->willReturn( false );
		$this->setService( 'WatchlistManager', $watchlistManager );

		$permissions = $this->buildPermissionsObject( $title );
		$this->assertFalse( $permissions->isAllowed( IMinervaPagePermissions::WATCH ) );
	}

	/**
	 * @covers ::isAllowed
	 */
	public function testMoveAndDeleteAndProtectNotAllowedByDefault() {
		$perms = $this->buildPermissionsObject( Title::makeTitle( NS_MAIN, 'Test' ) );
		$this->assertFalse( $perms->isAllowed( IMinervaPagePermissions::MOVE ) );
		$this->assertFalse( $perms->isAllowed( IMinervaPagePermissions::DELETE ) );
		$this->assertFalse( $perms->isAllowed( IMinervaPagePermissions::PROTECT ) );
	}

	/**
	 * @covers ::isAllowed
	 */
	public function testMoveAndDeleteAndProtectAllowedForUserWithPermissions() {
		$title = $this->createMock( Title::class );
		$title
			->method( 'exists' )
			->willReturn( true );
		$title->expects( $this->once() )
			->method( 'getContentModel' )
			->willReturn( CONTENT_MODEL_WIKITEXT );

		$perms = $this->buildPermissionsObject(
			$title,
			[],
			null,
			$this->mockRegisteredUltimateAuthority()
		);
		$this->assertTrue( $perms->isAllowed( IMinervaPagePermissions::MOVE ) );
		$this->assertTrue( $perms->isAllowed( IMinervaPagePermissions::DELETE ) );
		$this->assertTrue( $perms->isAllowed( IMinervaPagePermissions::PROTECT ) );
	}
}

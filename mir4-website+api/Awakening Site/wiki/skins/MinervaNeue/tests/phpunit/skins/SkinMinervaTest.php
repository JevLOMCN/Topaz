<?php

namespace MediaWiki\Minerva;

use MediaWiki\Minerva\Skins\SkinMinerva;
use MediaWiki\Output\OutputPage;
use MediaWiki\Title\Title;
use MediaWikiIntegrationTestCase;
use RequestContext;
use Wikimedia\TestingAccessWrapper;

/**
 * @coversDefaultClass \MediaWiki\Minerva\Skins\SkinMinerva
 * @group MinervaNeue
 */
class SkinMinervaTest extends MediaWikiIntegrationTestCase {
	private const ATTRIBUTE_NOTIFICATION_HREF = [
		'key' => 'href',
		'value' => '/wiki/Special:Notifications',
	];
	private const ATTRIBUTE_NOTIFICATION_DATA_EVENT_NAME = [
		'key' => 'data-event-name',
		'value' => 'ui.notifications',
	];

	private const ATTRIBUTE_NOTIFICATION_DATA_COUNTER_TEXT = [
		'key' => 'data-counter-text',
		'value' => "13",
	];
	private const ATTRIBUTE_NOTIFICATION_DATA_COUNTER_NUM = [
		'key' => 'data-counter-num',
		'value' => 13,
	];
	private const ATTRIBUTE_NOTIFICATION_TITLE = [
		'key' => 'title',
		'value' => "Your alerts",
	];

	private function newSkinMinerva() {
		$services = $this->getServiceContainer();
		return new SkinMinerva(
			$services->getGenderCache(),
			$services->getLinkRenderer(),
			$services->getService( 'Minerva.LanguagesHelper' ),
			$services->getService( 'Minerva.Menu.Definitions' ),
			$services->getService( 'Minerva.Permissions' ),
			$services->getService( 'Minerva.SkinOptions' ),
			$services->getService( 'Minerva.SkinUserPageHelper' ),
			$services->getNamespaceInfo(),
			$services->getRevisionLookup(),
			$services->getUserIdentityUtils(),
			$services->getUserOptionsManager()
		);
	}

	/**
	 * @param array $options
	 */
	private function overrideSkinOptions( $options ) {
		$services = $this->getServiceContainer();
		$mockOptions = new SkinOptions(
			$services->getHookContainer(),
			$services->getService( 'Minerva.SkinUserPageHelper' )
		);
		$mockOptions->setMultiple( $options );
		$this->setService( 'Minerva.SkinOptions', $mockOptions );
	}

	public static function provideHasPageActions() {
		return [
			[ NS_MAIN, 'test', 'view', true ],
			[ NS_SPECIAL, 'test', 'view', false ],
			[ NS_MAIN, 'Main Page', 'view', false ],
			[ NS_MAIN, 'test', 'history', false ]
		];
	}

	/**
	 * @dataProvider provideHasPageActions
	 * @covers ::hasPageActions
	 */
	public function testHasPageActions( int $namespace, string $title, string $action, bool $expected ) {
		$context = new RequestContext();
		$context->setTitle( Title::makeTitle( $namespace, $title ) );
		$context->setActionName( $action );

		$skin = $this->newSkinMinerva();
		$skin->setContext( $context );

		$this->assertEquals( $expected, TestingAccessWrapper::newFromObject( $skin )->hasPageActions() );
	}

	public static function provideHasPageTabs() {
		return [
			[ [ SkinOptions::TABS_ON_SPECIALS => false ], NS_MAIN, 'test', 'view', true ],
			[ [ SkinOptions::TABS_ON_SPECIALS => false ], NS_MAIN, 'Main Page', 'view', false ],
			[ [ SkinOptions::TABS_ON_SPECIALS => false ], NS_TALK, 'Main Page', 'view', false ],
			[ [ SkinOptions::TALK_AT_TOP => false ], NS_MAIN, 'test', 'view', false ],
			[ [ SkinOptions::TALK_AT_TOP => false ], NS_SPECIAL, 'test', 'view', true ],
			[ [ SkinOptions::TALK_AT_TOP => false ], NS_MAIN, 'test', 'history', true ],
		];
	}

	/**
	 * @dataProvider provideHasPageTabs
	 * @covers ::hasPageTabs
	 */
	public function testHasPageTabs( array $options, int $namespace, string $title, string $action, bool $expected ) {
		// both tabs on specials and talk at top default to true
		$this->overrideSkinOptions( $options );

		$context = new RequestContext();
		$context->setTitle( Title::makeTitle( $namespace, $title ) );
		$context->setActionName( $action );

		// hasPageTabs gets the action directly from the request rather than the context so we set it here as well
		$context->getRequest()->setVal( 'action', $action );

		$skin = $this->newSkinMinerva();
		$skin->setContext( $context );

		$this->assertEquals( $expected, TestingAccessWrapper::newFromObject( $skin )->hasPageTabs() );
	}

	/**
	 * @covers ::getTabsData
	 */
	public function testGetTabsData() {
		$context = new RequestContext();
		$context->setTitle( Title::makeTitle( NS_MAIN, 'test' ) );

		$skin = $this->newSkinMinerva();
		$skin->setContext( $context );

		$contentNavigationUrls = [ 'associated-pages' => [ 'testkey' => 'testvalue' ] ];
		$associatedPages = [ 'id' => 'test' ];
		$data = TestingAccessWrapper::newFromObject( $skin )->getTabsData( $contentNavigationUrls, $associatedPages );

		$this->assertEquals( [ 'items' => [ 'testvalue' ], 'id' => 'test' ], $data );
	}

	/**
	 * @covers ::getTabsData when hasPageTabs is false
	 */
	public function testGetTabsDataNoPageTabs() {
		$context = new RequestContext();
		$context->setTitle( Title::makeTitle( NS_MAIN, 'Main Page' ) );

		$skin = $this->newSkinMinerva();
		$skin->setContext( $context );

		$contentNavigationUrls = [ 'associated-pages' => [ 'testkey' => 'testvalue' ] ];
		$associatedPages = [ 'id' => 'test' ];
		$data = TestingAccessWrapper::newFromObject( $skin )->getTabsData( $contentNavigationUrls, $associatedPages );

		$this->assertEquals( [], $data );
	}

	/**
	 * @covers ::getTabsData when contentNavigationUrls is empty
	 */
	public function testGetTabsDataNoContentNavigationUrls() {
		$context = new RequestContext();
		$context->setTitle( Title::makeTitle( NS_MAIN, 'test' ) );

		$skin = $this->newSkinMinerva();
		$skin->setContext( $context );

		$contentNavigationUrls = [];
		$associatedPages = [ 'id' => 'test' ];
		$data = TestingAccessWrapper::newFromObject( $skin )->getTabsData( $contentNavigationUrls, $associatedPages );

		$this->assertEquals( [], $data );
	}

	/**
	 * @covers ::getTabsData when associatedPages has no id
	 */
	public function testGetTabsDataNoId() {
		$context = new RequestContext();
		$context->setTitle( Title::makeTitle( NS_MAIN, 'test' ) );

		$skin = $this->newSkinMinerva();
		$skin->setContext( $context );

		$contentNavigationUrls = [ 'associated-pages' => [ 'testkey' => 'testvalue' ] ];
		$associatedPages = [];
		$data = TestingAccessWrapper::newFromObject( $skin )->getTabsData( $contentNavigationUrls, $associatedPages );

		$this->assertEquals( [ 'items' => [ 'testvalue' ], 'id' => null ], $data );
	}

	/**
	 * @covers ::getHtmlElementAttributes when night mode is not enabled via feature flag or query params
	 */
	public function testGetHtmlElementAttributesNoNightMode() {
		$skin = $this->newSkinMinerva();

		$classes = $skin->getHtmlElementAttributes()['class'];
		$this->assertStringNotContainsString( 'skin-theme-clientpref-', $classes );
	}

	/**
	 * @covers ::getHtmlElementAttributes when night mode is enabled via feature flag
	 */
	public function testGetHtmlElementAttributesNightMode() {
		$this->overrideSkinOptions( [ SkinOptions::NIGHT_MODE => true ] );

		$skin = $this->newSkinMinerva();

		$classes = $skin->getHtmlElementAttributes()['class'];
		$this->assertStringContainsString( 'skin-theme-clientpref-day', $classes );
	}

	/**
	 * @covers ::getHtmlElementAttributes when night mode is set via query params
	 */
	public function testGetHtmlElementAttributesNightModeQueryParam() {
		$context = new RequestContext();
		$request = $context->getRequest();
		$request->setVal( 'minervanightmode', '1' );

		$skin = $this->newSkinMinerva();
		$skin->setContext( $context );

		$classes = $skin->getHtmlElementAttributes()['class'];
		$this->assertStringContainsString( 'skin-theme-clientpref-night', $classes );
	}

	/**
	 * @covers ::getHtmlElementAttributes when night mode is set via query params to an invalid option
	 */
	public function testGetHtmlElementAttributesNightModeQueryParamInvalid() {
		$context = new RequestContext();
		$request = $context->getRequest();
		$request->setVal( 'minervanightmode', '3' );

		$skin = $this->newSkinMinerva();
		$skin->setContext( $context );

		$classes = $skin->getHtmlElementAttributes()['class'];
		$this->assertStringContainsString( 'skin-theme-clientpref-day', $classes );
	}

	/**
	 * @covers ::getHtmlElementAttributes when night mode is enabled and the value is not default
	 */
	public function testGetHtmlElementAttributesNightModeUserOption() {
		$this->overrideSkinOptions( [ SkinOptions::NIGHT_MODE => true ] );

		$skin = $this->newSkinMinerva();

		$user = $skin->getUser();
		$this->getServiceContainer()->getUserOptionsManager()->setOption( $user, 'minerva-theme', 'day' );

		$classes = $skin->getHtmlElementAttributes()['class'];
		$this->assertStringContainsString( 'skin-theme-clientpref-day', $classes );
	}

	/**
	 * @covers ::getHtmlElementAttributes when night mode is enabled with non-default, and query param is invalid
	 */
	public function testGetHtmlElementAttributesNightModeUserOptionQueryParamInvalid() {
		$this->overrideSkinOptions( [ SkinOptions::NIGHT_MODE => true ] );

		$context = new RequestContext();
		$request = $context->getRequest();
		$request->setVal( 'minervanightmode', '3' );

		$skin = $this->newSkinMinerva();
		$skin->setContext( $context );

		$user = $skin->getUser();
		$this->getServiceContainer()->getUserOptionsManager()->setOption( $user, 'minerva-theme', 'day' );

		$classes = $skin->getHtmlElementAttributes()['class'];
		$this->assertStringContainsString( 'skin-theme-clientpref-day', $classes );
	}

	/**
	 * @covers ::setContext
	 * @covers ::hasCategoryLinks
	 */
	public function testHasCategoryLinksWhenOptionIsOff() {
		$outputPage = $this->getMockBuilder( OutputPage::class )
			->disableOriginalConstructor()
			->getMock();
		$outputPage->expects( $this->never() )
			->method( 'getCategoryLinks' );

		$this->overrideSkinOptions( [ SkinOptions::CATEGORIES => false ] );
		$context = new RequestContext();
		$context->setTitle( Title::makeTitle( NS_MAIN, 'Test' ) );
		$context->setOutput( $outputPage );

		$skin = $this->newSkinMinerva();
		$skin->setContext( $context );
		$skin = TestingAccessWrapper::newFromObject( $skin );

		$this->assertFalse( $skin->hasCategoryLinks() );
	}

	/**
	 * @dataProvider provideHasCategoryLinks
	 * @param array $categoryLinks
	 * @param bool $expected
	 * @covers ::setContext
	 * @covers ::hasCategoryLinks
	 */
	public function testHasCategoryLinks( array $categoryLinks, $expected ) {
		$outputPage = $this->getMockBuilder( OutputPage::class )
			->disableOriginalConstructor()
			->getMock();
		$outputPage->expects( $this->once() )
			->method( 'getCategoryLinks' )
			->willReturn( $categoryLinks );

		$this->overrideSkinOptions( [ SkinOptions::CATEGORIES => true ] );

		$context = new RequestContext();
		$context->setTitle( Title::makeTitle( NS_MAIN, 'Test' ) );
		$context->setOutput( $outputPage );

		$skin = $this->newSkinMinerva();
		$skin->setContext( $context );

		$skin = TestingAccessWrapper::newFromObject( $skin );

		$this->assertEquals( $expected, $skin->hasCategoryLinks() );
	}

	public static function provideHasCategoryLinks() {
		return [
			[ [], false ],
			[
				[
					'normal' => '<ul><li><a href="/wiki/Category:1">1</a></li></ul>'
				],
				true
			],
			[
				[
					'hidden' => '<ul><li><a href="/wiki/Category:Hidden">Hidden</a></li></ul>'
				],
				true
			],
			[
				[
					'normal' => '<ul><li><a href="/wiki/Category:1">1</a></li></ul>',
					'hidden' => '<ul><li><a href="/wiki/Category:Hidden">Hidden</a></li></ul>'
				],
				true
			],
			[
				[
					'unexpected' => '<ul><li><a href="/wiki/Category:1">1</a></li></ul>'
				],
				false
			],
		];
	}

	public static function provideGetNotificationButtons() {
		return [
			[
				[],
				[]
			],
			//
			// CIRCLE
			//
			[
				[
					'tag-name' => 'a',
					'classes' => 'mw-echo-notifications-badge mw-echo-notification-badge-nojs '
						. ' mw-echo-unseen-notifications',
					'array-attributes' => [
						self::ATTRIBUTE_NOTIFICATION_HREF,
						self::ATTRIBUTE_NOTIFICATION_DATA_COUNTER_TEXT,
						self::ATTRIBUTE_NOTIFICATION_DATA_COUNTER_NUM,
						self::ATTRIBUTE_NOTIFICATION_TITLE,
						[
							'key' => 'id',
							'value' => 'pt-notifications-alert',
						],
					],
					'data-icon' => [
						'icon' => 'circle'
					],
					'label' => 'Alerts (13)',
				],
				[
					[
						'name' => 'notifications-alert',
						'id' => 'pt-notifications-alert',
						'class' => 'notification-count notification-unseen mw-echo-unseen-notifications mw-list-item',
						'array-links' => [
							[
								'icon' => 'circle',
								'array-attributes' => [
									self::ATTRIBUTE_NOTIFICATION_HREF,
									self::ATTRIBUTE_NOTIFICATION_DATA_COUNTER_TEXT,
									self::ATTRIBUTE_NOTIFICATION_DATA_COUNTER_NUM,
									self::ATTRIBUTE_NOTIFICATION_TITLE,
									[
										'key' => 'class',
										'value' => 'mw-echo-notifications-badge '
											. 'mw-echo-notification-badge-nojs oo-ui-icon-bellOutline '
											. 'mw-echo-unseen-notifications',
									],
								],
								'text' => 'Alerts (13)'
							]
						]
					]
				]

			],
			//
			// BELL
			//
			[
				[
					'tag-name' => 'a',
					'classes' => 'mw-echo-notifications-badge mw-echo-notification-badge-nojs',
					'array-attributes' => [
						self::ATTRIBUTE_NOTIFICATION_HREF,
						self::ATTRIBUTE_NOTIFICATION_DATA_COUNTER_TEXT,
						self::ATTRIBUTE_NOTIFICATION_DATA_COUNTER_NUM,
						self::ATTRIBUTE_NOTIFICATION_TITLE,
						[
							'key' => 'id',
							'value' => 'pt-notifications-alert',
						],
					],
					'data-icon' => [
						'icon' => 'bellOutline-base20'
					],
					'label' => 'Alerts (13)',
				],
				[
					[
						'html-item' => 'n/a',
						'name' => 'notifications-alert',
						'html' => 'HTML',
						'id' => 'pt-notifications-alert',
						'class' => 'mw-list-item',
						'array-links' => [
							[
								'icon' => 'bellOutline-base20',
								'array-attributes' => [
									self::ATTRIBUTE_NOTIFICATION_HREF,
									self::ATTRIBUTE_NOTIFICATION_DATA_COUNTER_TEXT,
									self::ATTRIBUTE_NOTIFICATION_DATA_COUNTER_NUM,
									self::ATTRIBUTE_NOTIFICATION_TITLE,
									[
										'key' => 'class',
										'value' => 'mw-echo-notifications-badge mw-echo-notification-badge-nojs',
									],
								],
								'text' => 'Alerts (13)'
							]
						]
					]
				]
			]
		];
	}

	/**
	 * @dataProvider provideGetNotificationButtons
	 * @param array $expected
	 * @param array $from
	 * @covers ::getNotificationButtons
	 */
	public function testGetNotificationButtons( $expected, $from ) {
		$btns = SkinMinerva::getNotificationButtons( $from );
		$this->assertEquals( $expected['classes'] ?? '', $btns[0]['classes'] ?? '' );
		$this->assertEquals( $expected['data-attributes'] ?? [], $btns[0]['data-attributes'] ?? [] );
		$this->assertEquals( $expected['data-icon'] ?? [], $btns[0]['data-icon'] ?? [] );
		$this->assertEquals( $expected['data-label'] ?? '', $btns[0]['data-label'] ?? '' );
	}
}

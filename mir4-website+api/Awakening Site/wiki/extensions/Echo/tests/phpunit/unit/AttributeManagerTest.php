<?php

use MediaWiki\Extension\Notifications\AttributeManager;
use MediaWiki\User\Options\UserOptionsLookup;
use MediaWiki\User\UserGroupManager;
use MediaWiki\User\UserIdentity;
use MediaWiki\User\UserIdentityValue;

/**
 * @covers \MediaWiki\Extension\Notifications\AttributeManager
 */
class AttributeManagerTest extends MediaWikiUnitTestCase {

	private function getAttributeManager(
		array $notifications,
		array $categories = [],
		array $defaultNotifyTypeAvailability = [],
		array $notifyTypeAvailabilityByCategory = []
	): AttributeManager {
		$userGroupManager = $this->createNoOpMock( UserGroupManager::class, [ 'getUserGroups' ] );
		$userGroupManager->method( 'getUserGroups' )->willReturn( [ 'echo_group' ] );

		$userOptionsLookup = $this->createNoOpMock( UserOptionsLookup::class, [ 'getOption' ] );
		$userOptionsLookup->method( 'getOption' )->willReturn( true );

		return new AttributeManager(
			$notifications,
			$categories,
			$defaultNotifyTypeAvailability,
			$notifyTypeAvailabilityByCategory,
			$userGroupManager,
			$userOptionsLookup
		);
	}

	/**
	 * @return UserIdentity
	 */
	protected function getUser(): UserIdentity {
		return new UserIdentityValue( 1, 'ExampleUserName' );
	}

	public static function getUserLocatorsProvider() {
		return [
			[
				'No errors when requesting unknown type',
				// expected result
				[],
				// event type
				'foo',
				// notification configuration
				[],
			],

			[
				'Returns selected notification configuration',
				// expected result
				[ 'woot!' ],
				// event type
				'magic',
				// notification configuration
				[
					'foo' => [
						AttributeManager::ATTR_LOCATORS => [ 'frown' ],
					],
					'magic' => [
						AttributeManager::ATTR_LOCATORS => [ 'woot!' ],
					],
				],
			],

			[
				'Accepts user-locators as string and returns array',
				// expected result
				[ 'sagen' ],
				// event type
				'challah',
				// notification configuration
				[
					'challah' => [
						AttributeManager::ATTR_LOCATORS => 'sagen',
					],
				],
			],
		];
	}

	/**
	 * @dataProvider getUserLocatorsProvider
	 */
	public function testGetUserLocators( $message, $expect, $type, $notifications ) {
		$manager = $this->getAttributeManager( $notifications );

		$result = $manager->getUserCallable( $type, AttributeManager::ATTR_LOCATORS );
		$this->assertEquals( $expect, $result, $message );
	}

	public function testGetCategoryEligibility() {
		$notif = [
			'event_one' => [
				'category' => 'category_one'
			],
		];
		$category = [
			'category_one' => [
				'priority' => 10
			]
		];
		$manager = $this->getAttributeManager( $notif, $category );
		$this->assertTrue( $manager->getCategoryEligibility( $this->getUser(), 'category_one' ) );
		$category = [
			'category_one' => [
				'priority' => 10,
				'usergroups' => [
					'sysop'
				]
			]
		];
		$manager = $this->getAttributeManager( $notif, $category );
		$this->assertFalse( $manager->getCategoryEligibility( $this->getUser(), 'category_one' ) );
	}

	public function testGetNotificationCategory() {
		$notif = [
			'event_one' => [
				'category' => 'category_one'
			],
		];
		$category = [
			'category_one' => [
				'priority' => 10
			]
		];
		$manager = $this->getAttributeManager( $notif, $category );
		$this->assertEquals( 'category_one', $manager->getNotificationCategory( 'event_one' ) );

		$manager = $this->getAttributeManager( $notif );
		$this->assertEquals( 'other', $manager->getNotificationCategory( 'event_one' ) );

		$notif = [
			'event_one' => [
				'category' => 'category_two'
			],
		];
		$category = [
			'category_one' => [
				'priority' => 10
			]
		];
		$manager = $this->getAttributeManager( $notif, $category );
		$this->assertEquals( 'other', $manager->getNotificationCategory( 'event_one' ) );
	}

	public function testGetCategoryPriority() {
		$notif = [
			'event_one' => [
				'category' => 'category_two'
			],
		];
		$category = [
			'category_one' => [
				'priority' => 6
			],
			'category_two' => [
				'priority' => 100
			],
			'category_three' => [
				'priority' => -10
			],
			'category_four' => []
		];
		$manager = $this->getAttributeManager( $notif, $category );
		$this->assertSame( 6, $manager->getCategoryPriority( 'category_one' ) );
		$this->assertSame( 10, $manager->getCategoryPriority( 'category_two' ) );
		$this->assertSame( 10, $manager->getCategoryPriority( 'category_three' ) );
		$this->assertSame( 10, $manager->getCategoryPriority( 'category_four' ) );
	}

	public function testGetNotificationPriority() {
		$notif = [
			'event_one' => [
				'category' => 'category_one'
			],
			'event_two' => [
				'category' => 'category_two'
			],
			'event_three' => [
				'category' => 'category_three'
			],
			'event_four' => [
				'category' => 'category_four'
			]
		];
		$category = [
			'category_one' => [
				'priority' => 6
			],
			'category_two' => [
				'priority' => 100
			],
			'category_three' => [
				'priority' => -10
			],
			'category_four' => []
		];
		$manager = $this->getAttributeManager( $notif, $category );
		$this->assertSame( 6, $manager->getNotificationPriority( 'event_one' ) );
		$this->assertSame( 10, $manager->getNotificationPriority( 'event_two' ) );
		$this->assertSame( 10, $manager->getNotificationPriority( 'event_three' ) );
		$this->assertSame( 10, $manager->getNotificationPriority( 'event_four' ) );
	}

	public static function getEventsForSectionProvider() {
		$notifications = [
			'event_one' => [
				'category' => 'category_one',
				'section' => 'message',
			],
			'event_two' => [
				'category' => 'category_two',
				'section' => 'invalid',
			],
			'event_three' => [
				'category' => 'category_three',
				'section' => 'message',
			],
			'event_four' => [
				'category' => 'category_four',
				// Omitted
			],
			'event_five' => [
				'category' => 'category_two',
				'section' => 'alert',
			],
		];

		return [
			[
				[ 'event_one', 'event_three' ],
				$notifications,
				'message',
				'Messages',
			],

			[
				[ 'event_two', 'event_four', 'event_five' ],
				$notifications,
				'alert',
				'Alerts',
			],
		];
	}

	/**
	 * @dataProvider getEventsForSectionProvider
	 */
	public function testGetEventsForSection( $expected, $notificationTypes, $section, $message ) {
		$am = $this->getAttributeManager( $notificationTypes );
		$actual = $am->getEventsForSection( $section );
		$this->assertEquals( $expected, $actual, $message );
	}

	public function testGetUserEnabledEvents() {
		$notif = [
			'event_one' => [
				'category' => 'category_one'
			],
			'event_two' => [
				'category' => 'category_two'
			],
			'event_three' => [
				'category' => 'category_three'
			],
			'event_four' => [
				'category' => 'category_four'
			]
		];
		$category = [
			'category_one' => [
				'priority' => 10,
				'usergroups' => [
					'sysop'
				]
			],
			'category_two' => [
				'priority' => 10,
				'usergroups' => [
					'echo_group'
				]
			],
			'category_three' => [
				'priority' => 10,
			],
			'category_four' => [
				'priority' => 10,
			]
		];
		$defaultNotifyTypeAvailability = [
			'web' => true,
			'email' => true,
		];
		$notifyTypeAvailabilityByCategory = [
			'category_three' => [
				'web' => false,
				'email' => true,
			]
		];
		$manager = $this->getAttributeManager(
			$notif,
			$category,
			$defaultNotifyTypeAvailability,
			$notifyTypeAvailabilityByCategory
		);
		$this->assertEquals(
			[ 'event_two', 'event_four' ],
			$manager->getUserEnabledEvents( $this->getUser(), 'web' )
		);
		$this->assertEquals(
			[ 'event_two', 'event_three', 'event_four' ],
			$manager->getUserEnabledEvents( $this->getUser(), [ 'web', 'email' ] )
		);
	}

	public function testGetUserEnabledEventsBySections() {
		$notif = [
			'event_one' => [
				'category' => 'category_one'
			],
			'event_two' => [
				'category' => 'category_two',
				'section' => 'message'
			],
			'event_three' => [
				'category' => 'category_three',
				'section' => 'alert'
			],
			'event_four' => [
				'category' => 'category_three',
			],
			'event_five' => [
				'category' => 'category_five'
			]
		];
		$category = [
			'category_one' => [
				'priority' => 10,
			],
			'category_two' => [
				'priority' => 10,
			],
			'category_three' => [
				'priority' => 10
			],
			'category_five' => [
				'priority' => 10
			]
		];
		$defaultNotifyTypeAvailability = [
			'web' => true,
			'email' => true,
		];
		$notifyTypeAvailabilityByCategory = [
			'category_five' => [
				'web' => false,
				'email' => true,
			]
		];
		$manager = $this->getAttributeManager(
			$notif,
			$category,
			$defaultNotifyTypeAvailability,
			$notifyTypeAvailabilityByCategory
		);
		$expected = [ 'event_one', 'event_three', 'event_four' ];
		$actual = $manager->getUserEnabledEventsBySections( $this->getUser(), 'web', [ 'alert' ] );
		sort( $expected );
		sort( $actual );
		$this->assertEquals( $expected, $actual );

		$expected = [ 'event_one', 'event_three', 'event_four', 'event_five' ];
		$actual = $manager->getUserEnabledEventsBySections( $this->getUser(), [ 'web', 'email' ], [ 'alert' ] );
		sort( $expected );
		sort( $actual );
		$this->assertEquals( $expected, $actual );

		$expected = [ 'event_two' ];
		$actual = $manager->getUserEnabledEventsBySections( $this->getUser(), 'web', [ 'message' ] );
		sort( $expected );
		sort( $actual );
		$this->assertEquals( $expected, $actual );

		$expected = [ 'event_one', 'event_two', 'event_three', 'event_four' ];
		$actual = $manager->getUserEnabledEventsBySections( $this->getUser(), 'web',
			[ 'message', 'alert' ] );
		sort( $expected );
		sort( $actual );
		$this->assertEquals( $expected, $actual );
	}

	public static function getEventsByCategoryProvider() {
		return [
			[
				'Mix of populated and empty categories handled appropriately',
				[
					'category_one' => [
						'event_two',
						'event_five',
					],
					'category_two' => [
						'event_one',
						'event_three',
						'event_four',
					],
					'category_three' => [],
				],
				[
					'category_one' => [],
					'category_two' => [],
					'category_three' => [],
				],
				[
					'event_one' => [
						'category' => 'category_two',
					],
					'event_two' => [
						'category' => 'category_one',
					],
					'event_three' => [
						'category' => 'category_two',
					],
					'event_four' => [
						'category' => 'category_two',
					],
					'event_five' => [
						'category' => 'category_one',
					],
				]
			]
		];
	}

	/**
	 * @dataProvider getEventsByCategoryProvider
	 */
	public function testGetEventsByCategory(
		$message,
		$expectedMapping,
		$categories,
		$notifications
	) {
		$am = $this->getAttributeManager( $notifications, $categories );
		$actualMapping = $am->getEventsByCategory();
		$this->assertEquals( $expectedMapping, $actualMapping, $message );
	}

	public static function isNotifyTypeAvailableForCategoryProvider() {
		return [
			[
				'Fallback to default entirely',
				true,
				'category_one',
				'web',
				[ 'web' => true, 'email' => true ],
				[]
			],
			[
				'Fallback to default for single type',
				false,
				'category_two',
				'email',
				[ 'web' => true, 'email' => false ],
				[
					'category_two' => [
						'web' => true,
					],
				]
			],
			[
				'Use override',
				false,
				'category_three',
				'web',
				[ 'web' => true, 'email' => true ],
				[
					'category_three' => [
						'web' => false,
					],
				],
			],
		];
	}

	/**
	 * @dataProvider isNotifyTypeAvailableForCategoryProvider
	 */
	public function testIsNotifyTypeAvailableForCategory(
		$message,
		$expected,
		$categoryName,
		$notifyType,
		$defaultNotifyTypeAvailability,
		$notifyTypeAvailabilityByCategory
	) {
		$am = $this->getAttributeManager(
			[],
			[],
			$defaultNotifyTypeAvailability,
			$notifyTypeAvailabilityByCategory
		);
		$actual = $am->isNotifyTypeAvailableForCategory( $categoryName, $notifyType );
		$this->assertEquals( $expected, $actual, $message );
	}

	public static function isNotifyTypeDismissableForCategoryProvider() {
		return [
			[
				'Not dismissable because of all',
				false,
				[
					'category_one' => [
						'no-dismiss' => [ 'all' ],
					]
				],
				'category_one',
				'web',
			],
			[
				'Not dismissable because of specific notify type',
				false,
				[
					'category_two' => [
						'no-dismiss' => [ 'email' ],
					]
				],
				'category_two',
				'email',
			],
			[
				'Dismissable because of different affected notify type',
				true,
				[
					'category_three' => [
						'no-dismiss' => [ 'web' ],
					]
				],
				'category_three',
				'email',
			],
		];
	}

	/**
	 * @dataProvider isNotifyTypeDismissableForCategoryProvider
	 */
	public function testIsNotifyTypeDismissableForCategory(
		$message,
		$expected,
		$categories,
		$categoryName,
		$notifyType
	) {
		$am = $this->getAttributeManager( [], $categories );
		$actual = $am->isNotifyTypeDismissableForCategory( $categoryName, $notifyType );
		$this->assertEquals( $expected, $actual, $message );
	}

	public static function getNotificationSectionProvider() {
		yield [ 'event_one', 'alert' ];
		yield [ 'event_two', 'message' ];
		yield [ 'event_three', 'alert' ];
		yield [ 'event_undefined', 'alert' ];
	}

	/**
	 * @dataProvider getNotificationSectionProvider
	 */
	public function testGetNotificationSection( $type, $expected ) {
		$am = $this->getAttributeManager( [
			'event_one' => [
				'section' => 'alert',
			],
			'event_two' => [
				'section' => 'message',
			],
			'event_three' => [],
		] );
		$actual = $am->getNotificationSection( $type );
		$this->assertSame( $expected, $actual );
	}

	public static function isBundleExpandableProvider() {
		yield [ 'event_one', false ];
		yield [ 'event_two', false ];
		yield [ 'event_three', false ];
		yield [ 'event_four', true ];
		yield [ 'event_undefined', false ];
	}

	/**
	 * @dataProvider isBundleExpandableProvider
	 */
	public function testIsBundleExpandable( $type, $expected ) {
		$am = $this->getAttributeManager( [
			'event_one' => [],
			'event_two' => [
				'bundle' => [
					'web' => true
				]
			],
			'event_three' => [
				'bundle' => [
					'web' => true,
					'email' => false,
					'expandable' => false
				]
			],
			'event_four' => [
				'bundle' => [
					'web' => true,
					'email' => true,
					'expandable' => true
				]
			],
		] );
		$actual = $am->isBundleExpandable( $type );
		$this->assertSame( $expected, $actual );
	}

}

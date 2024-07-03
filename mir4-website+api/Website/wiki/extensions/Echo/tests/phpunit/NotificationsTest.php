<?php

use MediaWiki\Extension\Notifications\Mapper\NotificationMapper;
use MediaWiki\Extension\Notifications\Model\Event;
use MediaWiki\User\User;

/**
 * Tests for the built in notification types
 *
 * @group Database
 */
class NotificationsTest extends MediaWikiIntegrationTestCase {

	/** @var User */
	private $sysop;

	protected function setUp(): void {
		parent::setUp();
		$this->sysop = $this->getTestSysop()->getUser();
	}

	/**
	 * Helper function to get a user's latest notification
	 * @param User $user
	 * @return Event
	 */
	public static function getLatestNotification( $user ) {
		$notifMapper = new NotificationMapper();
		$notifs = $notifMapper->fetchUnreadByUser( $user, 1, '', [ 'user-rights' ] );
		$notif = array_pop( $notifs );

		return $notif->getEvent();
	}

	/**
	 * @covers \MediaWiki\Extension\Notifications\Hooks::onUserGroupsChanged
	 */
	public function testUserRightsNotif() {
		$user = new User();
		$user->setName( 'Dummy' );
		$user->addToDatabase();

		$context = new DerivativeContext( RequestContext::getMain() );
		$context->setUser( $this->sysop );
		$ur = $this->getServiceContainer()
			->getSpecialPageFactory()
			->getPage( 'Userrights' );
		$ur->setContext( $context );
		$ur->doSaveUserGroups( $user, [ 'sysop' ], [], 'reason' );
		$event = self::getLatestNotification( $user );
		$this->assertEquals( 'user-rights', $event->getType() );
		$this->assertEquals( $this->sysop->getName(), $event->getAgent()->getName() );
		$extra = $event->getExtra();
		$this->assertArrayHasKey( 'add', $extra );
		$this->assertArrayEquals( [ 'sysop' ], $extra['add'] );
		$this->assertArrayEquals( [], $extra['remove'] );
	}

}

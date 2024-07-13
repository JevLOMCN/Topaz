<?php

use MediaWiki\Extension\Notifications\DbFactory;
use MediaWiki\Extension\Notifications\Mapper\NotificationMapper;
use MediaWiki\Title\Title;

/**
 * @group Echo
 * @group Database
 */
class ThankYouEditTest extends MediaWikiIntegrationTestCase {

	private function deleteEchoData() {
		$db = DbFactory::newFromDefault()->getEchoDb( DB_PRIMARY );
		$db->delete( 'echo_event', '*', __METHOD__ );
		$db->delete( 'echo_notification', '*', __METHOD__ );
	}

	/**
	 * @covers \MediaWiki\Extension\Notifications\Hooks::onPageSaveComplete
	 */
	public function testFirstEdit() {
		// setup
		$this->deleteEchoData();
		$user = $this->getMutableTestUser()->getUser();
		$title = Title::newFromText( 'Help:MWEchoThankYouEditTest_testFirstEdit' );

		// action
		$this->editPage( $title, 'this is my first edit', '', NS_MAIN, $user );

		// assertions
		$notificationMapper = new NotificationMapper();
		$notifications = $notificationMapper->fetchByUser( $user, 10, null, [ 'thank-you-edit' ] );
		$this->assertCount( 1, $notifications );

		/** @var Notification $notification */
		$notification = reset( $notifications );
		$this->assertSame( 1, $notification->getEvent()->getExtraParam( 'editCount', 'not found' ) );
	}

	/**
	 * @covers \MediaWiki\Extension\Notifications\Hooks::onPageSaveComplete
	 */
	public function testTenthEdit() {
		// setup
		$this->deleteEchoData();
		$user = $this->getMutableTestUser()->getUser();
		$title = Title::newFromText( 'Help:MWEchoThankYouEditTest_testTenthEdit' );
		$page = $this->getServiceContainer()->getWikiPageFactory()->newFromTitle( $title );

		// action
		// we could fast-forward the edit-count to speed things up
		// but this is the only way to make sure duplicate notifications
		// are not generated
		for ( $i = 0; $i < 12; $i++ ) {
			$this->editPage( $page, "this is edit #$i", '', NS_MAIN, $user );
		}
		$user->clearInstanceCache();

		// assertions
		$notificationMapper = new NotificationMapper();
		$notifications = $notificationMapper->fetchByUser( $user, 10, null, [ 'thank-you-edit' ] );
		$this->assertCount( 2, $notifications );

		/** @var Notification $notification */
		$notification = reset( $notifications );
		$this->assertSame( 10, $notification->getEvent()->getExtraParam( 'editCount', 'not found' ) );
	}
}

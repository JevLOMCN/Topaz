<?php

use MediaWiki\Extension\Notifications\Push\Utils;
use MediaWiki\Extension\Notifications\Services;
use Wikimedia\TestingAccessWrapper;

/**
 * @group Database
 * @covers \MediaWiki\Extension\Notifications\Push\SubscriptionManager
 */
class SubscriptionManagerTest extends MediaWikiIntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->setMwGlobals( 'wgEchoPushMaxSubscriptionsPerUser', 1 );
	}

	public function testManagePushSubscriptions(): void {
		$subscriptionManagerBase = Services::getInstance()->getPushSubscriptionManager();
		$subscriptionManager = TestingAccessWrapper::newFromObject( $subscriptionManagerBase );

		$user = $this->getTestUser()->getUser();
		$centralId = Utils::getPushUserId( $user );

		$subscriptionManager->create( 'test', 'ABC123', $centralId );
		$subscriptions = $subscriptionManager->getSubscriptionsForUser( $centralId );
		$this->assertCount( 1, $subscriptions );

		$subscriptionManager->delete( [ 'ABC123' ], $centralId );
		$subscriptions = $subscriptionManager->getSubscriptionsForUser( $centralId );
		$this->assertCount( 0, $subscriptions );

		$subscriptionManager->create( 'test', 'ABC123', $centralId );
		$subscriptions = $subscriptionManager->getSubscriptionsForUser( $centralId );
		$this->assertCount( 1, $subscriptions );

		$subscriptionManager->create( 'test', 'DEF456', $centralId );
		$subscriptions = $subscriptionManager->getSubscriptionsForUser( $centralId );
		$this->assertCount( 1, $subscriptions );
		$this->assertEquals( 'DEF456', $subscriptions[0]->getToken() );
	}

}

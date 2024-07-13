<?php

use MediaWiki\Extension\Notifications\Push\Utils;
use MediaWiki\Extension\Notifications\Services;
use MediaWiki\User\User;

/**
 * @group medium
 * @group API
 * @group Database
 * @covers \MediaWiki\Extension\Notifications\Push\Api\ApiEchoPushSubscriptionsCreate
 */
class ApiEchoPushSubscriptionsCreateTest extends ApiTestCase {

	/** @var User */
	private $user;

	protected function setUp(): void {
		parent::setUp();
		$this->setMwGlobals( [
			'wgEchoEnablePush' => true,
			'wgEchoPushMaxSubscriptionsPerUser' => 2
		] );
		$this->user = $this->getTestUser()->getUser();
		$this->createTestData();
	}

	public function testApiCreateSubscription(): void {
		// Before max subscriptions reached
		$params = [
			'action' => 'echopushsubscriptions',
			'command' => 'create',
			'provider' => 'fcm',
			'providertoken' => 'ABC123',
		];
		$result = $this->doApiRequestWithToken( $params, null, $this->user );
		$this->assertEquals( 'Success', $result[0]['create']['result'] );

		// Make sure it's possible to register a new token even when limit is reached
		$params['providertoken'] = 'DEF456';
		$result = $this->doApiRequestWithToken( $params, null, $this->user );
		$this->assertEquals( 'Success', $result[0]['create']['result'] );

		// Explicitly verify that the oldest token was removed
		$subscriptionManager = Services::getInstance()->getPushSubscriptionManager();
		$subscriptions = $subscriptionManager->getSubscriptionsForUser( Utils::getPushUserId( $this->user ) );
		foreach ( $subscriptions as $subscription ) {
			$this->assertNotEquals( 'XYZ789', $subscription->getToken() );
		}
	}

	public function testApiCreateSubscriptionTokenExists(): void {
		$params = [
			'action' => 'echopushsubscriptions',
			'command' => 'create',
			'provider' => 'fcm',
			'providertoken' => 'XYZ789',
		];
		$this->expectException( ApiUsageException::class );
		$this->doApiRequestWithToken( $params, null, $this->user );
	}

	public function testApiCreateApnsSubscriptionWithTopic(): void {
		$params = [
			'action' => 'echopushsubscriptions',
			'command' => 'create',
			'provider' => 'apns',
			'providertoken' => 'ABC123',
			'topic' => 'test',
		];
		$result = $this->doApiRequestWithToken( $params, null, $this->user );
		$this->assertEquals( 'Success', $result[0]['create']['result'] );
	}

	public function testApiCreateApnsSubscriptionWithoutTopic(): void {
		$params = [
			'action' => 'echopushsubscriptions',
			'command' => 'create',
			'provider' => 'apns',
			'providertoken' => 'DEF456',
		];
		$this->expectException( ApiUsageException::class );
		$this->doApiRequestWithToken( $params, null, $this->user );
	}

	private function createTestData(): void {
		$subscriptionManager = Services::getInstance()->getPushSubscriptionManager();
		$userId = Utils::getPushUserId( $this->user );
		$subscriptionManager->create( 'fcm', 'XYZ789', $userId );
	}

}

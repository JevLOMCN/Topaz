<?php

use MediaWiki\Extension\Notifications\Push\Utils;
use MediaWiki\Extension\Notifications\Services;
use MediaWiki\User\User;

/**
 * @group medium
 * @group API
 * @group Database
 * @covers \MediaWiki\Extension\Notifications\Push\Api\ApiEchoPushSubscriptionsDelete
 */
class ApiEchoPushSubscriptionsDeleteTest extends ApiTestCase {

	/** @var User */
	private $user;

	/** @var User */
	private $subscriptionManager;

	/** @var User */
	private $otherUser;

	protected function setUp(): void {
		parent::setUp();
		$this->setMwGlobals( [
			'wgEchoEnablePush' => true,
			'wgEchoPushMaxSubscriptionsPerUser' => 3
		] );

		// Use mutable users for our generic users so we don't get two references to the same User
		$this->user = $this->getMutableTestUser()->getUser();
		$this->otherUser = $this->getMutableTestUser()->getUser();
		$this->subscriptionManager = $this->getTestUser( 'push-subscription-manager' )->getUser();

		$this->createTestData();
	}

	public function testApiDeleteSubscription(): void {
		$params = [
			'action' => 'echopushsubscriptions',
			'command' => 'delete',
			'providertoken' => 'ABC',
		];
		$result = $this->doApiRequestWithToken( $params, null, $this->user );
		$this->assertEquals( 'Success', $result[0]['delete']['result'] );
	}

	public function testApiDeleteSubscriptionNotFound(): void {
		$params = [
			'action' => 'echopushsubscriptions',
			'command' => 'delete',
			'providertoken' => 'XYZ',
		];
		$this->expectException( ApiUsageException::class );
		$this->doApiRequestWithToken( $params, null, $this->user );
	}

	public function testApiDeleteSubscriptionWithOwnCentralId(): void {
		$params = [
			'action' => 'echopushsubscriptions',
			'command' => 'delete',
			'providertoken' => 'ABC',
			'centraluserid' => Utils::getPushUserId( $this->user ),
		];
		$result = $this->doApiRequestWithToken( $params, null, $this->user );
		$this->assertEquals( 'Success', $result[0]['delete']['result'] );
	}

	public function testApiDeleteSubscriptionWithOtherNonSubscriptionManagerUser(): void {
		$params = [
			'action' => 'echopushsubscriptions',
			'command' => 'delete',
			'providertoken' => 'ABC',
			'centraluserid' => Utils::getPushUserId( $this->user ),
		];
		$this->expectException( ApiUsageException::class );
		$this->doApiRequestWithToken( $params, null, $this->otherUser );
	}

	public function testApiDeleteSubscriptionWithPushSubscriptionManager(): void {
		$params = [
			'action' => 'echopushsubscriptions',
			'command' => 'delete',
			'providertoken' => 'ABC',
		];
		$result = $this->doApiRequestWithToken( $params, null, $this->subscriptionManager );
		$this->assertEquals( 'Success', $result[0]['delete']['result'] );
	}

	public function testApiDeleteSubscriptionProviderTokenEmpty(): void {
		$params = [
			'action' => 'echopushsubscriptions',
			'command' => 'delete',
			'providertoken' => ''
		];
		$this->expectException( ApiUsageException::class );
		$result = $this->doApiRequestWithToken( $params, null, $this->user );
	}

	private function createTestData(): void {
		$subscriptionManager = Services::getInstance()->getPushSubscriptionManager();
		$userId = Utils::getPushUserId( $this->user );
		$subscriptionManager->create( 'fcm', 'ABC', $userId );
	}

}

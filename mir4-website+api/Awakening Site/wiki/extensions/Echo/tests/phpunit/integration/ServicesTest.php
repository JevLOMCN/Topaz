<?php

namespace MediaWiki\Extension\Notifications\Test\Integration;

use MediaWiki\Extension\Notifications\AttributeManager;
use MediaWiki\Extension\Notifications\Push\NotificationServiceClient;
use MediaWiki\Extension\Notifications\Push\SubscriptionManager;
use MediaWiki\Extension\Notifications\Services;
use MediaWikiIntegrationTestCase;

/**
 * @covers \MediaWiki\Extension\Notifications\Services
 * @group Database
 */
class ServicesTest extends MediaWikiIntegrationTestCase {

	/** @var Services */
	private $echoServices;

	protected function setUp(): void {
		parent::setUp();
		$this->echoServices = Services::getInstance();
	}

	public function testWrap(): void {
		$services = Services::wrap( $this->getServiceContainer() );
		$this->assertInstanceOf( Services::class, $services );
	}

	public function testGetPushNotificationServiceClient(): void {
		$serviceClient = $this->echoServices->getPushNotificationServiceClient();
		$this->assertInstanceOf( NotificationServiceClient::class, $serviceClient );
	}

	public function testGetPushSubscriptionManager(): void {
		$subscriptionManager = $this->echoServices->getPushSubscriptionManager();
		$this->assertInstanceOf( SubscriptionManager::class, $subscriptionManager );
	}

	public function testGetAttributeManager(): void {
		$attributeManager = $this->echoServices->getAttributeManager();
		$this->assertInstanceOf( AttributeManager::class, $attributeManager );
	}

}

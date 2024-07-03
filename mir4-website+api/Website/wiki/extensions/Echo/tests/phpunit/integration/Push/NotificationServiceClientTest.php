<?php

use MediaWiki\Extension\Notifications\Services;
use Wikimedia\TestingAccessWrapper;

/** @covers \MediaWiki\Extension\Notifications\Push\NotificationServiceClient */
class NotificationServiceClientTest extends MediaWikiIntegrationTestCase {
	use MockHttpTrait;

	public function testConstructRequest(): void {
		$this->installMockHttp( 'hi' );

		$client = Services::getInstance()->getPushNotificationServiceClient();
		$client = TestingAccessWrapper::newFromObject( $client );
		$payload = [ 'deviceTokens' => [ 'foo' ], 'messageType' => 'checkEchoV1' ];
		$request = $client->constructRequest( 'fcm', $payload );
		$this->assertInstanceOf( MWHttpRequest::class, $request );
	}

}

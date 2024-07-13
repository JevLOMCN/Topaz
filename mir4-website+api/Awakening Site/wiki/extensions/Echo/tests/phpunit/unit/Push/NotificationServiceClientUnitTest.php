<?php

use MediaWiki\Extension\Notifications\Push\NotificationServiceClient;
use MediaWiki\Extension\Notifications\Push\Subscription;

/** @covers \MediaWiki\Extension\Notifications\Push\NotificationServiceClient */
class NotificationServiceClientUnitTest extends MediaWikiUnitTestCase {

	/**
	 * @dataProvider sendCheckEchoRequestsProvider
	 */
	public function testSendCheckEchoRequests( $numOfCalls, $subscriptions, $expected ): void {
		$mock = $this->getMockBuilder( NotificationServiceClient::class )
			->disableOriginalConstructor()
			->onlyMethods( [ 'sendRequest' ] )
			->getMock();

		$mock->expects( $this->exactly( $numOfCalls ) )
			->method( 'sendRequest' )
			->willReturnCallback( function ( $provider, $payload ) use ( &$expected ): void {
				$expectedIdx = array_search( [ $provider, $payload ], $expected, true );
				$this->assertNotFalse( $expectedIdx, "Unexpected arguments (provider: $provider)" );
				unset( $expected[$expectedIdx] );
			} );

		$mock->sendCheckEchoRequests( $subscriptions );
	}

	public static function sendCheckEchoRequestsProvider(): array {
		$row = (object)[
			'eps_token' => 'JKL123',
			'epp_name' => 'fcm',
			'ept_text' => null,
			'eps_updated' => '2020-01-01 10:10:10',
		];
		$subscriptions[] = Subscription::newFromRow( $row );

		$row = (object)[
			'eps_token' => 'DEF456',
			'epp_name' => 'fcm',
			'ept_text' => null,
			'eps_updated' => '2020-01-01 10:10:10',
		];
		$subscriptions[] = Subscription::newFromRow( $row );

		$row = (object)[
			'eps_token' => 'GHI789',
			'epp_name' => 'apns',
			'ept_text' => 'test',
			'eps_updated' => '2020-01-01 10:10:10',
		];
		$subscriptions[] = Subscription::newFromRow( $row );

		return [
			[
				1,
				[ $subscriptions[0], $subscriptions[1] ],
				[
					[
						'fcm',
						[
							'deviceTokens' => [ "JKL123", 'DEF456' ],
							'messageType' => 'checkEchoV1'
						]
					]
				]
			],
			[
				2,
				$subscriptions,
				[
					[
						'fcm',
						[
							'deviceTokens' => [ "JKL123", 'DEF456' ],
							'messageType' => 'checkEchoV1'
						]
					],
					[
						'apns',
						[
							'deviceTokens' => [ 'GHI789' ],
							'messageType' => 'checkEchoV1',
							'topic' => 'test'
						]
					]
				]
			]
		];
	}

}

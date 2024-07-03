<?php

/**
 * @group medium
 * @group API
 * @covers \MediaWiki\Extension\Notifications\Push\Api\ApiEchoPushSubscriptions
 */
class ApiEchoPushSubscriptionsTest extends ApiTestCase {

	public function testRequiresToken(): void {
		$this->setMwGlobals( 'wgEchoEnablePush', true );
		$params = [
			'action' => 'echopushsubscriptions',
			'command' => 'create',
			'platform' => 'apns',
			'platformtoken' => 'ABC123',
		];
		$this->expectException( ApiUsageException::class );
		$this->doApiRequest( $params );
	}

}

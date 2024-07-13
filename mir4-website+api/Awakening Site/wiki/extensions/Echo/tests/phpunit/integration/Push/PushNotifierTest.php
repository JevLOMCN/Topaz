<?php

use MediaWiki\Extension\Notifications\Push\NotificationRequestJob;
use MediaWiki\Extension\Notifications\Push\PushNotifier;
use MediaWiki\User\CentralId\CentralIdLookup;
use MediaWiki\User\User;
use Wikimedia\TestingAccessWrapper;

/** @covers \MediaWiki\Extension\Notifications\Push\PushNotifier */
class PushNotifierTest extends MediaWikiIntegrationTestCase {

	public function testCreateJob(): void {
		$user = $this->createMock( User::class );
		$centralId = 42;
		$centralIdLookup = $this->createMock( CentralIdLookup::class );
		$centralIdLookup->method( 'centralIdFromLocalUser' )
			->with( $user )
			->willReturn( 42 );
		$this->setService( 'CentralIdLookup', $centralIdLookup );
		$notifier = TestingAccessWrapper::newFromClass( PushNotifier::class );
		$job = $notifier->createJob( $user );
		$this->assertInstanceOf( NotificationRequestJob::class, $job );
		$this->assertSame( 'EchoPushNotificationRequest', $job->getType() );
		$this->assertSame( $centralId, $job->getParams()['centralId'] );
	}

}

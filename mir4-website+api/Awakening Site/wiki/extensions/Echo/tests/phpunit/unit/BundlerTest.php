<?php

use MediaWiki\Extension\Notifications\Bundler;
use MediaWiki\Extension\Notifications\Model\Notification;

/**
 * @covers \MediaWiki\Extension\Notifications\Bundler
 */
class BundlerTest extends MediaWikiUnitTestCase {

	public function testBundle() {
		$read = true;
		$unread = false;
		$n1 = $this->createNotificationForBundling( 'bundle_hash_1', 'timestamp_4', $read );
		$n2 = $this->createNotificationForBundling( 'bundle_hash_1', 'timestamp_1', $read );
		$n3 = $this->createNotificationForBundling( 'bundle_hash_2', 'timestamp_3', $unread );
		$n4 = $this->createNotificationForBundling( 'bundle_hash_2', 'timestamp_2', $unread );
		$n5 = $this->createNotificationForBundling( 'bundle_hash_2', 'timestamp_5', $read );
		$notifications = [ $n1, $n2, $n3, $n4, $n5 ];

		$bundler = new Bundler();
		$bundledNotifications = $bundler->bundle( $notifications );

		$this->assertCount( 4, $bundledNotifications );
		$this->assertSame( $n5, $bundledNotifications[0] );
		$this->assertSame( $n1, $bundledNotifications[1] );
		$this->assertSame( $n3, $bundledNotifications[2] );
		$this->assertCount( 1, $bundledNotifications[2]->getBundledNotifications() );
		$this->assertSame( $n4, $bundledNotifications[2]->getBundledNotifications()[0] );
		$this->assertSame( $n2, $bundledNotifications[3] );
	}

	private function createNotificationForBundling( $bundleHash, $timestamp, $readStatus ) {
		$mock = $this->getMockBuilder( Notification::class )
			->disableOriginalConstructor()
			->onlyMethods( [
				'getBundlingKey',
				'getSortingKey',
				'canBeBundled',
			] )
			->getMock();

		$mock->method( 'getBundlingKey' )->willReturn( $bundleHash );
		$mock->method( 'getSortingKey' )->willReturn( $timestamp );
		$mock->method( 'canBeBundled' )->willReturn( !$readStatus );

		return $mock;
	}

}

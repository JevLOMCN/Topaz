<?php

namespace MediaWiki\Extension\Notifications;

use MediaWiki\Extension\Notifications\Cache\RevisionLocalCache;
use MediaWiki\Extension\Notifications\Cache\TitleLocalCache;
use MediaWiki\Extension\Notifications\Push\NotificationServiceClient;
use MediaWiki\Extension\Notifications\Push\SubscriptionManager;
use MediaWiki\MediaWikiServices;

class Services {

	/** @var MediaWikiServices */
	private $services;

	/** @return Services */
	public static function getInstance(): Services {
		return new self( MediaWikiServices::getInstance() );
	}

	/**
	 * @param MediaWikiServices $services
	 * @return Services
	 */
	public static function wrap( MediaWikiServices $services ): Services {
		return new self( $services );
	}

	/** @param MediaWikiServices $services */
	public function __construct( MediaWikiServices $services ) {
		$this->services = $services;
	}

	/** @return NotificationServiceClient */
	public function getPushNotificationServiceClient(): NotificationServiceClient {
		return $this->services->getService( 'EchoPushNotificationServiceClient' );
	}

	/** @return SubscriptionManager */
	public function getPushSubscriptionManager(): SubscriptionManager {
		return $this->services->getService( 'EchoPushSubscriptionManager' );
	}

	/** @return AttributeManager */
	public function getAttributeManager(): AttributeManager {
		return $this->services->getService( 'EchoAttributeManager' );
	}

	/** @return TitleLocalCache */
	public function getTitleLocalCache(): TitleLocalCache {
		return $this->services->getService( 'EchoTitleLocalCache' );
	}

	/** @return RevisionLocalCache */
	public function getRevisionLocalCache(): RevisionLocalCache {
		return $this->services->getService( 'EchoRevisionLocalCache' );
	}
}

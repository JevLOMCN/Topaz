<?php

use LoginNotify\LoginNotify;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;

return [
	'LoginNotify.LoginNotify' => static function ( MediaWikiServices $services ): LoginNotify {
		return new LoginNotify(
			new ServiceOptions(
				LoginNotify::CONSTRUCTOR_OPTIONS,
				$services->getMainConfig()
			),
			$services->getMainObjectStash(),
			LoggerFactory::getInstance( 'LoginNotify' ),
			$services->getStatsdDataFactory(),
			$services->getDBLoadBalancerFactory(),
			$services->getJobQueueGroup(),
			$services->getCentralIdLookup(),
			$services->getAuthManager()
		);
	}
];

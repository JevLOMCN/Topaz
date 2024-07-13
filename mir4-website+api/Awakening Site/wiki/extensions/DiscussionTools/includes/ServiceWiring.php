<?php

namespace MediaWiki\Extension\DiscussionTools;

use MediaWiki\MediaWikiServices;

// PHP unit does not understand code coverage for this file
// as the @covers annotation cannot cover a specific file
// This is fully tested in ServiceWiringTest.php
// @codeCoverageIgnoreStart

return [
	'DiscussionTools.CommentParser' => static function ( MediaWikiServices $services ): CommentParser {
		return new CommentParser(
			$services->getMainConfig(),
			$services->getContentLanguage(),
			$services->getLanguageConverterFactory(),
			$services->getService( 'DiscussionTools.LanguageData' ),
			$services->getTitleParser()
		);
	},
	'DiscussionTools.LanguageData' => static function ( MediaWikiServices $services ): LanguageData {
		return new LanguageData(
			$services->getMainConfig(),
			$services->getContentLanguage(),
			$services->getLanguageConverterFactory(),
			$services->getSpecialPageFactory()
		);
	},
	'DiscussionTools.SubscriptionStore' => static function ( MediaWikiServices $services ): SubscriptionStore {
		return new SubscriptionStore(
			$services->getConfigFactory(),
			$services->getDBLoadBalancerFactory(),
			$services->getReadOnlyMode(),
			$services->getUserFactory(),
			$services->getUserIdentityUtils()
		);
	},
	'DiscussionTools.ThreadItemStore' => static function ( MediaWikiServices $services ): ThreadItemStore {
		return new ThreadItemStore(
			$services->getConfigFactory(),
			$services->getDBLoadBalancerFactory(),
			$services->getReadOnlyMode(),
			$services->getPageStore(),
			$services->getRevisionStore(),
			$services->getTitleFormatter(),
			$services->getActorStore()
		);
	},
	'DiscussionTools.ThreadItemFormatter' => static function ( MediaWikiServices $services ): ThreadItemFormatter {
		return new ThreadItemFormatter(
			$services->getLinkRenderer()
		);
	},
];

// @codeCoverageIgnoreEnd

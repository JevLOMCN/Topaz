<?php

use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\Math\InputCheck\InputCheckFactory;
use MediaWiki\Extension\Math\Math;
use MediaWiki\Extension\Math\MathConfig;
use MediaWiki\Extension\Math\MathFormatter;
use MediaWiki\Extension\Math\MathWikibaseConnector;
use MediaWiki\Extension\Math\Render\RendererFactory;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use Wikibase\Client\WikibaseClient;
use Wikibase\Lib\Formatters\SnakFormatter;

return [
	'Math.CheckerFactory' => static function ( MediaWikiServices $services ): InputCheckFactory {
		return new InputCheckFactory(
			new ServiceOptions(
				InputCheckFactory::CONSTRUCTOR_OPTIONS,
				$services->getMainConfig()
			),
			$services->getMainWANObjectCache(),
			$services->getHttpRequestFactory(),
			LoggerFactory::getInstance( 'Math' )
		);
	},
	'Math.Config' => static function ( MediaWikiServices $services ): MathConfig {
		return new MathConfig(
			new ServiceOptions( MathConfig::CONSTRUCTOR_OPTIONS, $services->getMainConfig() ),
			ExtensionRegistry::getInstance()
		);
	},
	'Math.RendererFactory' => static function ( MediaWikiServices $services ): RendererFactory {
		return new RendererFactory(
			new ServiceOptions(
				RendererFactory::CONSTRUCTOR_OPTIONS,
				$services->getMainConfig()
			),
			Math::getMathConfig( $services ),
			$services->getUserOptionsLookup(),
			LoggerFactory::getInstance( 'Math' ),
			$services->getMainWANObjectCache()
		);
	},
	'Math.WikibaseConnector' => static function ( MediaWikiServices $services ): MathWikibaseConnector {
		return new MathWikibaseConnector(
			new ServiceOptions( MathWikibaseConnector::CONSTRUCTOR_OPTIONS, $services->getMainConfig() ),
			WikibaseClient::getRepoLinker( $services ),
			$services->getLanguageFactory(),
			$services->getLanguageNameUtils(),
			WikibaseClient::getEntityRevisionLookup( $services ),
			WikibaseClient::getFallbackLabelDescriptionLookupFactory( $services ),
			WikibaseClient::getSite( $services ),
			WikibaseClient::getEntityIdParser( $services ),
			new MathFormatter( SnakFormatter::FORMAT_HTML ),
			LoggerFactory::getInstance( 'Math' )
		);
	}
];

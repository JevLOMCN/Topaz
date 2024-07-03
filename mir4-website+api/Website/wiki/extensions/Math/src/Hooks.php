<?php
/**
 * MediaWiki math extension
 *
 * @copyright 2002-2015 various MediaWiki contributors
 * @license GPL-2.0-or-later
 */

namespace MediaWiki\Extension\Math;

// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

use ExtensionRegistry;
use Maintenance;
use MediaWiki\Config\ConfigException;
use MediaWiki\Hook\MaintenanceRefreshLinksInitHook;
use MediaWiki\MediaWikiServices;
use MediaWiki\Settings\SettingsBuilder;
use MediaWiki\SpecialPage\Hook\SpecialPage_initListHook;
use RequestContext;

class Hooks implements
	SpecialPage_initListHook,
	MaintenanceRefreshLinksInitHook
{

	/**
	 * Extension registration callback, used to apply dynamic defaults for configuration variables.
	 */
	public static function onConfig( array $extInfo, SettingsBuilder $settings ) {
		$config = $settings->getConfig();

		// Documentation of MathRestbaseInterface::getUrl() should be updated when this is changed.

		$fullRestbaseUrl = $config->get( 'MathFullRestbaseURL' );
		$internalRestbaseURL = $config->get( 'MathInternalRestbaseURL' );
		$useInternalRestbasePath = $config->get( 'MathUseInternalRestbasePath' );
		$virtualRestConfig = $config->get( 'VirtualRestConfig' );

		if ( !$fullRestbaseUrl ) {
			throw new ConfigException(
				'Math extension can not find Restbase URL. Please specify $wgMathFullRestbaseURL.'
			);
		}

		if ( !$useInternalRestbasePath ) {
			if ( $internalRestbaseURL ) {
				$settings->warning( "The MathInternalRestbaseURL setting will be ignored " .
					"because MathUseInternalRestbasePath is set to false." );
			}

			// Force the use of the external URL for internal calls as well.
			$settings->overrideConfigValue( 'MathInternalRestbaseURL', $fullRestbaseUrl );
		} elseif ( !$internalRestbaseURL ) {
			if ( isset( $virtualRestConfig['modules']['restbase'] ) ) {
				$settings->warning( "The MathInternalRestbaseURL is falling back to " .
					"VirtualRestConfig. Please set MathInternalRestbaseURL explicitly." );

				$restBaseUrl = $virtualRestConfig['modules']['restbase']['url'];
				$restBaseUrl = rtrim( $restBaseUrl, '/' );

				$restBaseDomain = $virtualRestConfig['modules']['restbase']['domain'] ?? 'localhost';

				// Ensure the correct domain format: strip protocol, port,
				// and trailing slash if present.  This lets us use
				// $wgCanonicalServer as a default value, which is very convenient.
				// XXX: This was copied from RestbaseVirtualRESTService. Use UrlUtils::parse instead?
				$restBaseDomain = preg_replace(
					'/^((https?:)?\/\/)?([^\/:]+?)(:\d+)?\/?$/',
					'$3',
					$restBaseDomain
				);

				$internalRestbaseURL = "$restBaseUrl/$restBaseDomain/";
			} else {
				// Default to using the external URL for internal calls as well.
				$internalRestbaseURL = $fullRestbaseUrl;
			}

			$settings->overrideConfigValue( 'MathInternalRestbaseURL', $internalRestbaseURL );
		}
	}

	public static function onExtensionFunctions() {
		if ( ExtensionRegistry::getInstance()->isLoaded( 'WikibaseClient' ) ) {
			global $wgRestAPIAdditionalRouteFiles;
			$wgRestAPIAdditionalRouteFiles[] = dirname( __DIR__ ) . '/popupRestRoutes.json';
		}
	}

	/**
	 * MaintenanceRefreshLinksInit handler; optimize settings for refreshLinks batch job.
	 *
	 * @param Maintenance $maint
	 */
	public function onMaintenanceRefreshLinksInit( $maint ) {
		$user = RequestContext::getMain()->getUser();

		// Don't parse LaTeX to improve performance
		MediaWikiServices::getInstance()->getUserOptionsManager()
			->setOption( $user, 'math', MathConfig::MODE_SOURCE );
	}

	/**
	 * Remove Special:MathWikibase if the Wikibase client extension isn't loaded
	 *
	 * @param array &$list
	 */
	public function onSpecialPage_initList( &$list ) {
		if ( !ExtensionRegistry::getInstance()->isLoaded( 'WikibaseClient' ) ) {
			unset( $list['MathWikibase'] );
		}
	}

}

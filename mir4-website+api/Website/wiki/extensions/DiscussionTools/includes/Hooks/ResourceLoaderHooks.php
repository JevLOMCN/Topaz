<?php
/**
 * DiscussionTools resource loader hooks
 *
 * @file
 * @ingroup Extensions
 * @license MIT
 */

namespace MediaWiki\Extension\DiscussionTools\Hooks;

use MediaWiki\Config\Config;
use MediaWiki\Config\ConfigFactory;
use MediaWiki\ResourceLoader\Hook\ResourceLoaderGetConfigVarsHook;

class ResourceLoaderHooks implements
	ResourceLoaderGetConfigVarsHook
{

	private Config $config;

	public function __construct(
		ConfigFactory $configFactory
	) {
		$this->config = $configFactory->makeConfig( 'discussiontools' );
	}

	/**
	 * Set static (not request-specific) JS configuration variables
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ResourceLoaderGetConfigVars
	 * @param array &$vars Array of variables to be added into the output of the startup module
	 * @param string $skin Current skin name to restrict config variables to a certain skin
	 * @param Config $config
	 */
	public function onResourceLoaderGetConfigVars( array &$vars, $skin, Config $config ): void {
		$abtest = $this->config->get( 'DiscussionToolsABTest' );
		if ( $abtest ) {
			$vars['wgDiscussionToolsABTest'] = $abtest;
		}
	}

}

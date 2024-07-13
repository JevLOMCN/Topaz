<?php

namespace MediaWiki\Extension\DiscussionTools\Hooks;

use MediaWiki\Config\Config;
use MediaWiki\Config\ConfigFactory;
use MediaWiki\Extension\BetaFeatures\Hooks\GetBetaFeaturePreferencesHook;
use MediaWiki\MainConfigNames;
use MediaWiki\User\User;

/**
 * Hooks from BetaFeatures extension,
 * which is optional to use with this extension.
 */
class BetaPreferenceHooks implements GetBetaFeaturePreferencesHook {

	private Config $coreConfig;
	private Config $config;

	public function __construct(
		Config $coreConfig,
		ConfigFactory $configFactory
	) {
		$this->coreConfig = $coreConfig;
		$this->config = $configFactory->makeConfig( 'discussiontools' );
	}

	/**
	 * Handler for the GetBetaFeaturePreferences hook, to add and hide user beta preferences as configured
	 */
	public function onGetBetaFeaturePreferences( User $user, array &$preferences ) {
		if ( $this->config->get( 'DiscussionToolsBeta' ) ) {
			$iconpath = $this->coreConfig->get( MainConfigNames::ExtensionAssetsPath ) . '/DiscussionTools/images';
			$preferences['discussiontools-betaenable'] = [
				'version' => '1.0',
				'label-message' => 'discussiontools-preference-label',
				'desc-message' => 'discussiontools-preference-description',
				'screenshot' => [
					'ltr' => "$iconpath/betafeatures-icon-DiscussionTools-ltr.svg",
					'rtl' => "$iconpath/betafeatures-icon-DiscussionTools-rtl.svg",
				],
				'info-message' => 'discussiontools-preference-info-link',
				'discussion-message' => 'discussiontools-preference-discussion-link',
				'requirements' => [
					'javascript' => true
				]
			];
		}
	}

}

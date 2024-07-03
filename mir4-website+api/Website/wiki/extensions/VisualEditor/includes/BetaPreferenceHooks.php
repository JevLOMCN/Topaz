<?php

namespace MediaWiki\Extension\VisualEditor;

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
		$this->config = $configFactory->makeConfig( 'visualeditor' );
	}

	/**
	 * Handler for the GetBetaFeaturePreferences hook, to add and hide user beta preferences as configured
	 *
	 * @param User $user
	 * @param array &$preferences
	 */
	public function onGetBetaFeaturePreferences( User $user, array &$preferences ) {
		if ( $this->config->get( 'VisualEditorEnableCollabBeta' ) ) {
			$iconpath = $this->coreConfig->get( MainConfigNames::ExtensionAssetsPath ) . '/VisualEditor/images';

			$preferences['visualeditor-collab'] = [
				'version' => '1.0',
				'label-message' => 'visualeditor-preference-collab-label',
				'desc-message' => 'visualeditor-preference-collab-description',
				'screenshot' => [
					'ltr' => "$iconpath/betafeatures-icon-collab-ltr.svg",
					'rtl' => "$iconpath/betafeatures-icon-collab-rtl.svg",
				],
				'info-message' => 'visualeditor-preference-collab-info-link',
				'discussion-message' => 'visualeditor-preference-collab-discussion-link',
				'requirements' => [
					'javascript' => true
				]
			];
		}
	}

}

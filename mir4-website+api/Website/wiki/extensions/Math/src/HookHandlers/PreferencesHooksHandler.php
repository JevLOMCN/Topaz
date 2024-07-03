<?php

namespace MediaWiki\Extension\Math\HookHandlers;

use MediaWiki\Extension\Math\MathConfig;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\Preferences\Hook\GetPreferencesHook;
use MediaWiki\User\Hook\UserGetDefaultOptionsHook;

class PreferencesHooksHandler implements
	UserGetDefaultOptionsHook,
	GetPreferencesHook
{

	/** @var MathConfig */
	private $mathConfig;

	/**
	 * @param MathConfig $mathConfig
	 */
	public function __construct(
		MathConfig $mathConfig
	) {
		$this->mathConfig = $mathConfig;
	}

	public function onUserGetDefaultOptions( &$defaultOptions ) {
		// Normalize the default use option in case it's not a valid rendering mode. BUG 64844
		$mode = $defaultOptions['math'] = MathConfig::normalizeRenderingMode( $defaultOptions['math'] );
		if ( !$this->mathConfig->isValidRenderingMode( $mode ) ) {
			$validModes = $this->mathConfig->getValidRenderingModes();
			LoggerFactory::getInstance( 'Math' )
				->error( "Misconfiguration: wgDefaultUserOptions['math'] is not an enabled mode", [
					'valid_modes' => $validModes,
					'configured_default' => $mode,
				] );
			$defaultOptions['math'] = $validModes[0];
		}
	}

	public function onGetPreferences( $user, &$preferences ) {
		$preferences['math'] = [
			'type' => 'radio',
			'options-messages' => array_flip( $this->mathConfig->getValidRenderingModeKeys() ),
			'label' => '&#160;',
			'section' => 'rendering/math',
		];

		$preferences['math-popups'] = [
			'type' => 'toggle',
			'label-message' => 'popups-settings-option-math-tooltip',
			'section' => 'rendering/math',
			'help-message' => 'popups-settings-option-math-tooltip-description'
		];
	}
}

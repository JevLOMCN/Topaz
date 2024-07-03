<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\ConfirmEdit\hCaptcha\Hooks;

use MediaWiki\Config\Config;
use MediaWiki\ResourceLoader as RL;

class ResourceLoaderHooks {
	/**
	 * Passes config variables to ext.confirmEdit.hCaptcha.visualEditor ResourceLoader module.
	 * @param RL\Context $context
	 * @param Config $config
	 * @return array
	 */
	public static function getHCaptchaResourceLoaderConfig(
		RL\Context $context,
		Config $config
	) {
		return [
			'hCaptchaSiteKey' => $config->get( 'HCaptchaSiteKey' ),
			'hCaptchaScriptURL' => 'https://js.hcaptcha.com/1/api.js',
		];
	}
}

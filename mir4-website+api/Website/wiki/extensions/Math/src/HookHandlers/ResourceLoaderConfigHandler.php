<?php

namespace MediaWiki\Extension\Math\HookHandlers;

use MediaWiki\Config\Config;
use MediaWiki\Extension\Math\MathConfig;
use MediaWiki\ResourceLoader\Hook\ResourceLoaderGetConfigVarsHook;

class ResourceLoaderConfigHandler implements
	ResourceLoaderGetConfigVarsHook
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

	public function onResourceLoaderGetConfigVars( array &$vars, $skin, Config $config ): void {
		$vars['wgMathEntitySelectorUrl'] = $this->mathConfig->getMathEntitySelectorUrl();
	}
}

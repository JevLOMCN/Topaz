<?php

namespace MediaWiki\Minerva\Hooks;

use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Minerva\SkinOptions;
use Skin;

/**
 * This is a hook runner class, see docs/Hooks.md in core.
 * @internal
 */
class HookRunner implements
	SkinMinervaOptionsInitHook
{
	private HookContainer $hookContainer;

	public function __construct( HookContainer $hookContainer ) {
		$this->hookContainer = $hookContainer;
	}

	/**
	 * @inheritDoc
	 */
	public function onSkinMinervaOptionsInit( Skin $skin, SkinOptions $skinOptions ) {
		return $this->hookContainer->run(
			'SkinMinervaOptionsInit',
			[ $skin, $skinOptions ]
		);
	}
}

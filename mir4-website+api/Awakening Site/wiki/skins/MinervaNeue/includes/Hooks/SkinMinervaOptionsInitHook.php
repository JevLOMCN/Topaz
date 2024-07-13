<?php

namespace MediaWiki\Minerva\Hooks;

use MediaWiki\Minerva\SkinOptions;
use Skin;

/**
 * This is a hook handler interface, see docs/Hooks.md in core.
 * Use the hook name "SkinMinervaOptionsInit" to register handlers implementing this interface.
 *
 * @stable to implement
 * @ingroup Hooks
 */
interface SkinMinervaOptionsInitHook {
	/**
	 * @param Skin $skin
	 * @param SkinOptions $skinOptions
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onSkinMinervaOptionsInit( Skin $skin, SkinOptions $skinOptions );
}

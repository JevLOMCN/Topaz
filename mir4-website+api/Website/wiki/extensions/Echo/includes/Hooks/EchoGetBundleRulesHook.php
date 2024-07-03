<?php

namespace MediaWiki\Extension\Notifications\Hooks;

use MediaWiki\Extension\Notifications\Model\Event;

/**
 * This is a hook handler interface, see docs/Hooks.md in core.
 * Use the hook name "EchoGetBundleRules" to register handlers implementing this interface.
 *
 * @stable to implement
 * @ingroup Hooks
 */
interface EchoGetBundleRulesHook {
	/**
	 * @param Event $event
	 * @param string &$bundleKey
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onEchoGetBundleRules( Event $event, string &$bundleKey );
}

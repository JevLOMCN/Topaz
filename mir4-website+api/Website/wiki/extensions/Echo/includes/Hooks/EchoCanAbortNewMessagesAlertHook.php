<?php

namespace MediaWiki\Extension\Notifications\Hooks;

/**
 * This is a hook handler interface, see docs/Hooks.md in core.
 * Use the hook name "EchoCanAbortNewMessagesAlert" to register handlers implementing this interface.
 *
 * @stable to implement
 * @ingroup Hooks
 */
interface EchoCanAbortNewMessagesAlertHook {
	/**
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onEchoCanAbortNewMessagesAlert();
}

<?php

namespace MediaWiki\Extension\Notifications\Hooks;

use MediaWiki\Extension\Notifications\Model\Event;
use MediaWiki\User\User;

/**
 * This is a hook handler interface, see docs/Hooks.md in core.
 * Use the hook name "EchoGetDefaultNotifiedUsers" to register handlers implementing this interface.
 *
 * @stable to implement
 * @ingroup Hooks
 */
interface EchoGetDefaultNotifiedUsersHook {
	/**
	 * @param Event $event
	 * @param User[] &$users
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onEchoGetDefaultNotifiedUsers( Event $event, array &$users );
}

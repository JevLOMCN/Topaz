<?php

namespace MediaWiki\Extension\Notifications\Hooks;

use MediaWiki\Extension\Notifications\Model\Event;
use MediaWiki\User\UserIdentity;

/**
 * This is a hook handler interface, see docs/Hooks.md in core.
 * Use the hook name "EchoAbortEmailNotification" to register handlers implementing this interface.
 *
 * @stable to implement
 * @ingroup Hooks
 */
interface EchoAbortEmailNotificationHook {
	/**
	 * @param UserIdentity $user
	 * @param Event $event
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onEchoAbortEmailNotification( UserIdentity $user, Event $event );
}

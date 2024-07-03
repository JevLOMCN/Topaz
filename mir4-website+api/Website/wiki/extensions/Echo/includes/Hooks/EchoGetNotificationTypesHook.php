<?php

namespace MediaWiki\Extension\Notifications\Hooks;

use MediaWiki\Extension\Notifications\Model\Event;
use MediaWiki\User\User;

/**
 * This is a hook handler interface, see docs/Hooks.md in core.
 * Use the hook name "EchoGetNotificationTypes" to register handlers implementing this interface.
 *
 * @stable to implement
 * @ingroup Hooks
 */
interface EchoGetNotificationTypesHook {
	/**
	 * @param User $user
	 * @param Event $event
	 * @param string[] &$userNotifyTypes
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onEchoGetNotificationTypes( User $user, Event $event, array &$userNotifyTypes );
}

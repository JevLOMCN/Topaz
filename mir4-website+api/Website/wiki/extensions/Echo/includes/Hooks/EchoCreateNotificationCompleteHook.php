<?php

namespace MediaWiki\Extension\Notifications\Hooks;

use MediaWiki\Extension\Notifications\Model\Notification;

/**
 * This is a hook handler interface, see docs/Hooks.md in core.
 * Use the hook name "EchoCreateNotificationComplete" to register handlers implementing this interface.
 *
 * @stable to implement
 * @ingroup Hooks
 */
interface EchoCreateNotificationCompleteHook {
	/**
	 * @param Notification $notification
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onEchoCreateNotificationComplete( Notification $notification );
}

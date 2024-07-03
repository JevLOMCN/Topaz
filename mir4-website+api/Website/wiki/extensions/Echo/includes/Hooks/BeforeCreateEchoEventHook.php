<?php

namespace MediaWiki\Extension\Notifications\Hooks;

/**
 * This is a hook handler interface, see docs/Hooks.md in core.
 * Use the hook name "BeforeCreateEchoEvent" to register handlers implementing this interface.
 *
 * @stable to implement
 * @ingroup Hooks
 */
interface BeforeCreateEchoEventHook {
	/**
	 * Called on setup of Echo extension
	 *
	 * @param array &$notifications To expand $wgEchoNnotifications
	 * @param array &$notificationCategories To expand $wgEchoNotificationCategories
	 * @param array &$notificationIcons To expand $wgEchoNotificationIcons
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onBeforeCreateEchoEvent(
		array &$notifications,
		array &$notificationCategories,
		array &$notificationIcons
	);
}

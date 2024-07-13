<?php

namespace MediaWiki\Extension\Notifications\Hooks;

use MediaWiki\Title\Title;
use MediaWiki\User\User;

/**
 * This is a hook handler interface, see docs/Hooks.md in core.
 * Use the hook name "BeforeDisplayOrangeAlert" to register handlers implementing this interface.
 *
 * @stable to implement
 * @ingroup Hooks
 */
interface BeforeDisplayOrangeAlertHook {
	/**
	 * @param User $user
	 * @param Title $title
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onBeforeDisplayOrangeAlert( User $user, Title $title );
}

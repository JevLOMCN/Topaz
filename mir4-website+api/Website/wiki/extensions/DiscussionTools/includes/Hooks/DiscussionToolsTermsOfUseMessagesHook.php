<?php

namespace MediaWiki\Extension\DiscussionTools\Hooks;

use MediaWiki\Config\Config;
use MessageLocalizer;

/**
 * This is a hook handler interface, see docs/Hooks.md in core.
 * Use the hook name "DiscussionToolsTermsOfUseMessages" to register handlers implementing this interface.
 *
 * @stable to implement
 * @ingroup Hooks
 */
interface DiscussionToolsTermsOfUseMessagesHook {
	/**
	 * @param array &$messages
	 * @param MessageLocalizer $context
	 * @param Config $config
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onDiscussionToolsTermsOfUseMessages( array &$messages, MessageLocalizer $context, Config $config );
}

<?php

namespace MediaWiki\Extension\DiscussionTools\Hooks;

use IContextSource;
use MediaWiki\Extension\DiscussionTools\OverflowMenuItem;

/**
 * This is a hook handler interface, see docs/Hooks.md in core.
 * Use the hook name "DiscussionToolsAddOverflowMenuItems" to register handlers implementing this interface.
 *
 * @stable to implement
 * @ingroup Hooks
 */
interface DiscussionToolsAddOverflowMenuItemsHook {

	/**
	 * Register menu items to add to the DiscussionTools overflow menu.
	 *
	 * These menu items appear in an overflow menu that opens via a button with an ellipsis icon.
	 * The button can be displayed adjacent to:
	 *  - topic headings
	 *  - individual comments
	 *
	 * @param OverflowMenuItem[] &$overflowMenuItems Menu items to add to the DiscussionTools
	 *   overflow/ellipsis menu adjacent to topic headings and individual comments.
	 * @param string[] &$resourceLoaderModules List of ResourceLoader modules that DiscussionTools
	 *   will load when adding the menu item to the overflow menu. Implementations of this hook
	 *   would typically add at least one item to the list. Make sure to include the relevant OOUI icon
	 *   ResourceLoader module associated with the 'icon' property of the OverflowMenuItem.
	 * @param array $threadItemData The relevant thread item for the overflow menu.
	 * @param IContextSource $contextSource Use this to obtain Title, User, Skin, Config, etc objects as needed.
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onDiscussionToolsAddOverflowMenuItems(
		array &$overflowMenuItems,
		array &$resourceLoaderModules,
		array $threadItemData,
		IContextSource $contextSource
	);
}

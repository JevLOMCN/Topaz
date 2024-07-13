<?php

namespace MediaWiki\Extension\ReplaceText\Hooks;

use MediaWiki\Title\Title;

/**
 * This is a hook handler interface, see docs/Hooks.md in core.
 * Use the hook name "ReplaceTextFilterPageTitlesForEdit" to register handlers implementing this interface.
 *
 * @stable to implement
 * @ingroup Hooks
 */
interface FilterPageTitlesForEditHook {
	/**
	 * Provides other extension the ability to avoid editing content on pages based on their titles.
	 *
	 * @param Title[] &$filteredTitles Array of page titles whose content will be edited
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onReplaceTextFilterPageTitlesForEdit( array &$filteredTitles );
}

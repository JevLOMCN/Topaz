<?php

namespace MediaWiki\Extension\Nuke\Hooks;

use MediaWiki\Title\Title;

interface NukeDeletePageHook {

	/**
	 * Hook runner for the `NukeDeletePage` hook
	 *
	 * Allows other extensions to handle the deletion of titles
	 *
	 * @param Title $title title to delete
	 * @param string $reason reason for deletion
	 * @param bool &$deletionResult Whether the deletion was successful or not
	 * @return bool|void True or no return value to let Nuke handle the deletion or
	 *  false if it was already handled in the hook.
	 */
	public function onNukeDeletePage( Title $title, string $reason, bool &$deletionResult );
}

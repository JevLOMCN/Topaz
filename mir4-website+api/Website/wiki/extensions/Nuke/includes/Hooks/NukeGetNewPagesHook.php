<?php

namespace MediaWiki\Extension\Nuke\Hooks;

interface NukeGetNewPagesHook {

	/**
	 * Hook runner for the `NukeGetNewPages` hook
	 *
	 * After searching for pages to delete. Can be used to add and remove pages.
	 *
	 * @param string $username username filter applied
	 * @param ?string $pattern pattern filter applied
	 * @param ?int $namespace namespace filter applied
	 * @param int $limit limit filter applied
	 * @param array &$pages page titles already retrieved
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onNukeGetNewPages(
		string $username,
		?string $pattern,
		?int $namespace,
		int $limit,
		array &$pages
	);

}

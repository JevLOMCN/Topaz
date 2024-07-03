<?php

namespace MediaWiki\Extension\AbuseFilter\Hooks;

use MediaWiki\Extension\AbuseFilter\Variables\VariableHolder;
use MediaWiki\Title\Title;
use RecentChange;

interface AbuseFilterGenerateTitleVarsHook {
	/**
	 * Hook runner for the `AbuseFilter-generateTitleVars` hook
	 *
	 * Allows altering the variables generated for a title
	 *
	 * @param VariableHolder $vars
	 * @param Title $title
	 * @param string $prefix Variable name prefix
	 * @param ?RecentChange $rc If the variables should be generated for an RC entry,
	 *     this is the entry. Null if it's for the current action being filtered.
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onAbuseFilter_generateTitleVars(
		VariableHolder $vars,
		Title $title,
		string $prefix,
		?RecentChange $rc
	);
}

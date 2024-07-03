<?php

namespace MediaWiki\Extension\AbuseFilter\Hooks;

use MediaWiki\Extension\AbuseFilter\Variables\VariableHolder;
use RecentChange;

interface AbuseFilterGenerateGenericVarsHook {
	/**
	 * Hook runner for the `AbuseFilter-generateGenericVars` hook
	 *
	 * Allows altering generic variables, i.e. independent from page and user
	 *
	 * @param VariableHolder $vars
	 * @param ?RecentChange $rc If the variables should be generated for an RC entry,
	 *     this is the entry. Null if it's for the current action being filtered.
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onAbuseFilter_generateGenericVars(
		VariableHolder $vars,
		?RecentChange $rc
	);
}

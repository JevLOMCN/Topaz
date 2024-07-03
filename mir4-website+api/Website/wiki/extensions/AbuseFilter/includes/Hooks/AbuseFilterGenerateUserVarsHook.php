<?php

namespace MediaWiki\Extension\AbuseFilter\Hooks;

use MediaWiki\Extension\AbuseFilter\Variables\VariableHolder;
use MediaWiki\User\User;
use RecentChange;

interface AbuseFilterGenerateUserVarsHook {
	/**
	 * Hook runner for the `AbuseFilter-generateUserVars` hook
	 *
	 * Allows altering the variables generated for a specific user
	 *
	 * @param VariableHolder $vars
	 * @param User $user
	 * @param ?RecentChange $rc If the variables should be generated for an RC entry,
	 *     this is the entry. Null if it's for the current action being filtered.
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onAbuseFilter_generateUserVars(
		VariableHolder $vars,
		User $user,
		?RecentChange $rc
	);
}

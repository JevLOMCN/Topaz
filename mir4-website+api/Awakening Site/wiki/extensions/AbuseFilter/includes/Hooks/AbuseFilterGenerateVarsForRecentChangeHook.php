<?php

namespace MediaWiki\Extension\AbuseFilter\Hooks;

use MediaWiki\Extension\AbuseFilter\VariableGenerator\RCVariableGenerator;
use MediaWiki\Extension\AbuseFilter\Variables\VariableHolder;
use MediaWiki\User\User;
use RecentChange;

interface AbuseFilterGenerateVarsForRecentChangeHook {
	/**
	 * Hook runner for the `AbuseFilterGenerateVarsForRecentChange` hook
	 *
	 * Hook that allows extensions to generate variables from a RecentChange row with a non-standard model.
	 * The hooks `AbuseFilterGenerate(Title|User|Generic)Hook` should be used for computing single variables
	 * in standard RC rows.
	 *
	 * @param RCVariableGenerator $generator
	 * @param RecentChange $rc
	 * @param VariableHolder $vars
	 * @param User $contextUser
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onAbuseFilterGenerateVarsForRecentChange(
		RCVariableGenerator $generator,
		RecentChange $rc,
		VariableHolder $vars,
		User $contextUser
	);
}

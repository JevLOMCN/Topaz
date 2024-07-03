<?php

namespace MediaWiki\Extension\AbuseFilter\Hooks;

use MediaWiki\Extension\AbuseFilter\Variables\VariableHolder;
use MediaWiki\Title\Title;
use MediaWiki\User\User;

interface AbuseFilterAlterVariablesHook {
	/**
	 * Hook runner for the `AbuseFilterAlterVariables` hook
	 *
	 * Allows overwriting of abusefilter variables just before they're
	 * checked against filters. Note that you may specify custom variables in a saner way using other hooks:
	 * AbuseFilter-generateTitleVars, AbuseFilter-generateUserVars and AbuseFilter-generateGenericVars.
	 *
	 * @param VariableHolder &$vars
	 * @param Title $title Title object target of the action
	 * @param User $user User object performer of the action
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onAbuseFilterAlterVariables(
		VariableHolder &$vars,
		Title $title,
		User $user
	);
}

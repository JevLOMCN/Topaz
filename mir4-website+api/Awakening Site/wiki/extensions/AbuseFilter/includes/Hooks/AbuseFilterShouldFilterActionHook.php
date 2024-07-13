<?php

namespace MediaWiki\Extension\AbuseFilter\Hooks;

use MediaWiki\Extension\AbuseFilter\Variables\VariableHolder;
use MediaWiki\Title\Title;
use MediaWiki\User\User;

interface AbuseFilterShouldFilterActionHook {
	/**
	 * Hook runner for the `AbuseFilterShouldFilterAction` hook
	 *
	 * Called before filtering an action. If the current action should not be filtered,
	 * return false and add a useful reason to $skipReasons.
	 *
	 * @param VariableHolder $vars
	 * @param Title $title Title object target of the action
	 * @param User $user User object performer of the action
	 * @param array &$skipReasons Array of reasons why the action should be skipped
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onAbuseFilterShouldFilterAction(
		VariableHolder $vars,
		Title $title,
		User $user,
		array &$skipReasons
	);
}

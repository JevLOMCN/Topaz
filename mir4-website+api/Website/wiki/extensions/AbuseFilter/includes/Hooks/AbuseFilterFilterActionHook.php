<?php

namespace MediaWiki\Extension\AbuseFilter\Hooks;

use MediaWiki\Extension\AbuseFilter\Variables\VariableHolder;
use MediaWiki\Title\Title;

interface AbuseFilterFilterActionHook {
	/**
	 * Hook runner for the `AbuseFilter-filterAction` hook
	 *
	 * DEPRECATED! Use AbuseFilterAlterVariables instead.
	 *
	 * Allows overwriting of abusefilter variables in FilterRunner::init just before they're
	 * checked against filters. Note that you may specify custom variables in a saner way using other hooks:
	 * AbuseFilter-generateTitleVars, AbuseFilter-generateUserVars and AbuseFilter-generateGenericVars.
	 *
	 * @param VariableHolder &$vars
	 * @param Title $title
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onAbuseFilter_filterAction(
		VariableHolder &$vars,
		Title $title
	);
}

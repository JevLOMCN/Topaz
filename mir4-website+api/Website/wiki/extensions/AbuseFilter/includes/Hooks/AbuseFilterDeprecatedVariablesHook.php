<?php

namespace MediaWiki\Extension\AbuseFilter\Hooks;

interface AbuseFilterDeprecatedVariablesHook {
	/**
	 * Hook runner for the `AbuseFilter-deprecatedVariables` hook
	 *
	 * Allows adding deprecated variables. If a filter uses an old variable, the parser
	 * will automatically translate it to the new one.
	 *
	 * @param array &$deprecatedVariables deprecated variables, syntax: [ 'old_name' => 'new_name' ]
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onAbuseFilter_deprecatedVariables( array &$deprecatedVariables );
}

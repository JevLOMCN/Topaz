<?php

namespace MediaWiki\Extension\AbuseFilter\Hooks;

use MediaWiki\Extension\AbuseFilter\Variables\VariableHolder;

interface AbuseFilterComputeVariableHook {
	/**
	 * Hook runner for the `AbuseFilter-computeVariable` hook
	 *
	 * Like AbuseFilter-interceptVariable but called if the requested method wasn't found.
	 * Return false to indicate that the method is known to the hook and was computed successfully.
	 *
	 * @param string $method Method to generate the variable
	 * @param VariableHolder $vars
	 * @param array $parameters Parameters with data to compute the value
	 * @param ?string &$result Result of the computation
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onAbuseFilter_computeVariable(
		string $method,
		VariableHolder $vars,
		array $parameters,
		?string &$result
	);
}

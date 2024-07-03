<?php

namespace MediaWiki\Extension\AbuseFilter\Hooks;

interface AbuseFilterBuilderHook {
	/**
	 * Hook runner for the `AbuseFilter-builder` hook
	 *
	 * Allows overwriting of the builder values, i.e. names and descriptions of
	 * the AbuseFilter language like variables.
	 *
	 * @param array &$realValues Builder values
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onAbuseFilter_builder( array &$realValues );
}

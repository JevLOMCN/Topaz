<?php

namespace MediaWiki\Extension\AbuseFilter\Hooks;

interface AbuseFilterGetDangerousActionsHook {
	/**
	 * Hook runner for the `AbuseFilterGetDangerousActions` hook
	 *
	 * Allows specifying custom consequences which can harm the user and prevent
	 * the edit from being saved.
	 *
	 * @param string[] &$actions The dangerous actions
	 */
	public function onAbuseFilterGetDangerousActions( array &$actions ): void;
}

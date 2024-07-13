<?php

namespace MediaWiki\Extension\AbuseFilter\Hooks;

// phpcs:ignore MediaWiki.Classes.UnusedUseStatement.UnusedUse
use MediaWiki\Extension\AbuseFilter\Consequences\Consequence\Consequence;
// phpcs:ignore MediaWiki.Classes.UnusedUseStatement.UnusedUse
use MediaWiki\Extension\AbuseFilter\Consequences\Parameters;

interface AbuseFilterCustomActionsHook {
	/**
	 * Hook runner for the `AbuseFilterCustomActions` hook
	 *
	 * Allows specifying custom actions. Callers should append to $actions, using the action name as (string) key,
	 * and the value should be a callable with the signature documented below.
	 *
	 * @param callable[] &$actions
	 * @phan-param array<string,callable(Parameters,array):Consequence> &$actions
	 */
	public function onAbuseFilterCustomActions( array &$actions );
}

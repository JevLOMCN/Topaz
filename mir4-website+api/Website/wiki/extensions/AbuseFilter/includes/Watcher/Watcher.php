<?php

namespace MediaWiki\Extension\AbuseFilter\Watcher;

/**
 * Classes inheriting this interface can be used to execute some actions after all filter have been checked.
 */
interface Watcher {
	/**
	 * @param int[] $localFilters The local filters that matched the action
	 * @param int[] $globalFilters The global filters that matched the action
	 * @param string $group
	 */
	public function run( array $localFilters, array $globalFilters, string $group ): void;
}

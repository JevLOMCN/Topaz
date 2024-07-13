<?php

namespace MediaWiki\Extension\Notifications;

/**
 * Interface providing list of contained values and an optional cache key to go along with it.
 */
interface ContainmentList {
	/**
	 * @return string[] The values contained within this list.
	 */
	public function getValues();

	/**
	 * @return string A string suitable for appending to the cache key prefix to facilitate
	 *                cache busting when the underlying data changes, or a blank string if
	 *                not relevant.
	 */
	public function getCacheKey();
}

<?php

namespace MediaWiki\Extension\Notifications;

/**
 * Indicates that an object can be bundled.
 */
interface Bundleable {

	/**
	 * @return bool Whether this object can be bundled.
	 */
	public function canBeBundled();

	/**
	 * @return string objects with the same bundling key can be bundled together
	 */
	public function getBundlingKey();

	/**
	 * @param Bundleable[] $bundleables other object that have been bundled with this one
	 */
	public function setBundledElements( array $bundleables );

	/**
	 * @return mixed the key by which this object should be sorted during the bundling process
	 */
	public function getSortingKey();
}

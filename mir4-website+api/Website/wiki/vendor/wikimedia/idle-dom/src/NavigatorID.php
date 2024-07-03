<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * NavigatorID
 *
 * @see https://dom.spec.whatwg.org/#interface-navigatorid
 *
 * @property string $appCodeName
 * @property string $appName
 * @property string $appVersion
 * @property string $platform
 * @property string $product
 * @property string $productSub
 * @property string $userAgent
 * @property string $vendor
 * @property string $vendorSub
 * @property string $oscpu
 * @phan-forbid-undeclared-magic-properties
 */
interface NavigatorID {
	/**
	 * @return string
	 */
	public function getAppCodeName(): string;

	/**
	 * @return string
	 */
	public function getAppName(): string;

	/**
	 * @return string
	 */
	public function getAppVersion(): string;

	/**
	 * @return string
	 */
	public function getPlatform(): string;

	/**
	 * @return string
	 */
	public function getProduct(): string;

	/**
	 * @return string
	 */
	public function getProductSub(): string;

	/**
	 * @return string
	 */
	public function getUserAgent(): string;

	/**
	 * @return string
	 */
	public function getVendor(): string;

	/**
	 * @return string
	 */
	public function getVendorSub(): string;

	/**
	 * @return bool
	 */
	public function taintEnabled(): bool;

	/**
	 * @return string
	 */
	public function getOscpu(): string;

}

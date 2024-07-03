<?php

namespace MediaWiki\Extension\ConfirmEdit\Store;

use BadMethodCallException;
use MediaWiki\Config\ConfigException;

abstract class CaptchaStore {
	/**
	 * Store the correct answer for a given captcha
	 * @param string $index
	 * @param array $info the captcha result
	 */
	abstract public function store( $index, $info );

	/**
	 * Retrieve the answer for a given captcha
	 * @param string $index
	 * @return array|false
	 */
	abstract public function retrieve( $index );

	/**
	 * Delete a result once the captcha has been used, so it cannot be reused
	 * @param string $index
	 */
	abstract public function clear( $index );

	/**
	 * Whether this type of CaptchaStore needs cookies
	 * @return bool
	 */
	abstract public function cookiesNeeded();

	/**
	 * The singleton instance
	 * @var CaptchaStore|null
	 */
	private static $instance;

	/**
	 * Get somewhere to store captcha data that will persist between requests
	 *
	 * @return CaptchaStore
	 */
	final public static function get() {
		global $wgCaptchaStorageClass;
		if ( !self::$instance instanceof self ) {
			if ( in_array( self::class, class_parents( $wgCaptchaStorageClass ) ) ) {
				self::$instance = new $wgCaptchaStorageClass;
			} else {
				throw new ConfigException( "Invalid CaptchaStore class $wgCaptchaStorageClass" );
			}
		}
		return self::$instance;
	}

	final public static function unsetInstanceForTests() {
		if ( !defined( 'MW_PHPUNIT_TEST' ) ) {
			throw new BadMethodCallException( 'Cannot unset ' . __CLASS__ . ' instance in operation.' );
		}
		self::$instance = null;
	}

	/**
	 * Protected constructor: no creating instances except through the factory method above
	 */
	protected function __construct() {
	}
}

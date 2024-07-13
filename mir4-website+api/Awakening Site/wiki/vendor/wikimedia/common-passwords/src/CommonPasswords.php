<?php

namespace Wikimedia\CommonPasswords;

class CommonPasswords {

	/**
	 * @internal
	 * @return array
	 */
	public static function getData() {
		static $data = null;
		if ( $data === null ) {
			$data = require __DIR__ . '/common.php';
		}
		return $data;
	}

	/**
	 * @param string $password Password to check if it's considered common
	 * @return bool
	 */
	public static function isCommon( $password ) {
		return isset( self::getData()[ $password ] );
	}
}

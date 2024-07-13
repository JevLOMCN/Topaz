<?php

namespace Wikimedia\RemexHtml;

use InvalidArgumentException;

/**
 * This is a statically configurable mechanism for preventing the setting of
 * undeclared properties on objects. The point of it is to detect programmer
 * errors.
 */
trait PropGuard {
	public static $armed = true;

	public function __set( $name, $value ) {
		if ( self::$armed ) {
			throw new InvalidArgumentException( "Property \"$name\" on object of class " . get_class( $this ) .
				" is undeclared" );
		} else {
			$this->$name = $value;
		}
	}
}

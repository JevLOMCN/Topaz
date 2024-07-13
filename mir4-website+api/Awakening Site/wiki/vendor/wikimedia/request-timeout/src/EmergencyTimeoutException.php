<?php

namespace Wikimedia\RequestTimeout;

/**
 * An exception which is thrown if a critical section is open for too long.
 */
class EmergencyTimeoutException extends TimeoutException {
	/**
	 * @param string $name The name of the critical section
	 * @param float $limit The limit (in seconds) which has expired
	 */
	public function __construct( $name, $limit ) {
		parent::__construct( "The critical section \"$name\" timed out after {limit} seconds",
			$limit );
	}
}

<?php

namespace Wikimedia\RequestTimeout;

/**
 * An exception which is thrown when the request timeout expires.
 */
class RequestTimeoutException extends TimeoutException {
	/**
	 * @param float $limit The limit (in seconds) which has expired
	 */
	public function __construct( $limit ) {
		parent::__construct( "The maximum execution time of {limit} seconds was exceeded",
			$limit );
	}
}

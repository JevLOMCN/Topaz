<?php

namespace Wikimedia\RequestTimeout;

use Wikimedia\NormalizedException\NormalizedException;

/**
 * The base class for timeout exceptions thrown by this library
 */
class TimeoutException extends NormalizedException {
	/** @var float */
	private $limit;

	public function __construct( $message, $limit ) {
		parent::__construct( $message, [ 'limit' => $limit ] );
		$this->limit = $limit;
	}

	/**
	 * Get the limit which was exceeded, in seconds.
	 *
	 * @return float
	 */
	public function getLimit() {
		return $this->limit;
	}
}

<?php

namespace MediaWiki\Extension\AbuseFilter\Filter;

use RuntimeException;

/**
 * @codeCoverageIgnore
 */
class ClosestFilterVersionNotFoundException extends RuntimeException {
	/**
	 * @param int $filterID
	 * @param int $historyID
	 */
	public function __construct( int $filterID, int $historyID ) {
		parent::__construct( "No version of filter $filterID closest to $historyID found" );
	}
}

<?php

namespace MediaWiki\Extension\AbuseFilter\Filter;

use RuntimeException;

/**
 * @codeCoverageIgnore
 */
class FilterNotFoundException extends RuntimeException {
	/**
	 * @param int $filter
	 * @param bool $global
	 */
	public function __construct( int $filter, bool $global ) {
		$msg = $global
			? "Global filter $filter does not exist"
			: "Filter $filter does not exist";
		parent::__construct( $msg );
	}
}

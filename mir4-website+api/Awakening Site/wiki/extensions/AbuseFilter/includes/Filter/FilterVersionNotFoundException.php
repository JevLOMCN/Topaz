<?php

namespace MediaWiki\Extension\AbuseFilter\Filter;

use RuntimeException;

/**
 * @codeCoverageIgnore
 */
class FilterVersionNotFoundException extends RuntimeException {
	/**
	 * @param int $version
	 */
	public function __construct( int $version ) {
		parent::__construct( "Filter version $version does not exist" );
	}
}

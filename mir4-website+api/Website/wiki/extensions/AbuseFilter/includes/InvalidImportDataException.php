<?php

namespace MediaWiki\Extension\AbuseFilter;

use InvalidArgumentException;

/**
 * @codeCoverageIgnore
 */
class InvalidImportDataException extends InvalidArgumentException {
	/**
	 * @param string $data That is not valid
	 */
	public function __construct( string $data ) {
		parent::__construct( "Invalid import data: $data" );
	}
}

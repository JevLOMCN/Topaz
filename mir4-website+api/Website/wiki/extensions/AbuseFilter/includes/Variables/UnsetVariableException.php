<?php

namespace MediaWiki\Extension\AbuseFilter\Variables;

use RuntimeException;

/**
 * @codeCoverageIgnore
 */
class UnsetVariableException extends RuntimeException {
	/**
	 * @param string $varName
	 */
	public function __construct( string $varName ) {
		parent::__construct( "Variable $varName is not set" );
	}
}

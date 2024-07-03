<?php

namespace MediaWiki\Extension\AbuseFilter\Parser\Exception;

/**
 * Exceptions thrown upon reaching the condition limit of the AbuseFilter parser.
 */
class ConditionLimitException extends ExceptionBase {
	public function __construct() {
		parent::__construct( 'Condition limit reached.' );
	}
}

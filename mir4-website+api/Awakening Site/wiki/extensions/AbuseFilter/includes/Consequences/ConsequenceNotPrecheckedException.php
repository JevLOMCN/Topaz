<?php

namespace MediaWiki\Extension\AbuseFilter\Consequences;

use RuntimeException;

/**
 * @codeCoverageIgnore
 */
class ConsequenceNotPrecheckedException extends RuntimeException {
	public function __construct() {
		parent::__construct(
			'Consequences that can disable other consequences should ' .
				'use shouldDisableOtherConsequences() before execute()'
		);
	}
}

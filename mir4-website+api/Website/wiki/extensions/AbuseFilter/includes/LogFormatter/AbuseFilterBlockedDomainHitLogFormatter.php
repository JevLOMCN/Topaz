<?php

namespace MediaWiki\Extension\AbuseFilter\LogFormatter;

use LogFormatter;
use Message;

class AbuseFilterBlockedDomainHitLogFormatter extends LogFormatter {
	/**
	 * @return array
	 * @suppress SecurityCheck-DoubleEscaped Known taint-check bug
	 */
	protected function getMessageParameters() {
		$params = parent::getMessageParameters();
		$params[3] = Message::rawParam( htmlspecialchars( $params[3] ) );
		return $params;
	}

}

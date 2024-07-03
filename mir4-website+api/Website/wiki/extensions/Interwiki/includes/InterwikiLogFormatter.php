<?php

namespace MediaWiki\Extension\Interwiki;

use LogFormatter;
use Message;

/**
 * Needed to pass the URL as a raw parameter, because it contains $1
 */
class InterwikiLogFormatter extends LogFormatter {
	/**
	 * @return array
	 * @suppress SecurityCheck-DoubleEscaped taint-check bug
	 */
	protected function getMessageParameters() {
		$params = parent::getMessageParameters();
		if ( isset( $params[4] ) ) {
			$params[4] = Message::rawParam( htmlspecialchars( $params[4] ) );
		}
		return $params;
	}
}

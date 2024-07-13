<?php

namespace MediaWiki\Extension\AbuseFilter\LogFormatter;

use LogFormatter;

class AbuseFilterSuppressLogFormatter extends LogFormatter {

	/**
	 * @return string
	 */
	protected function getMessageKey() {
		if ( $this->entry->getSubtype() === 'unhide-afl' ) {
			return 'abusefilter-log-entry-unsuppress';
		} else {
			return 'abusefilter-log-entry-suppress';
		}
	}

}

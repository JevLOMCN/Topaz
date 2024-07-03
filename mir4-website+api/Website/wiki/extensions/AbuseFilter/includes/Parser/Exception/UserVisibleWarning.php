<?php

namespace MediaWiki\Extension\AbuseFilter\Parser\Exception;

use Message;

/**
 * A variant of user-visible exception that is not fatal.
 */
class UserVisibleWarning extends UserVisibleException {
	/**
	 * @return Message
	 */
	public function getMessageObj(): Message {
		// Give grep a chance to find the usages:
		// abusefilter-parser-warning-match-empty-regex
		return new Message(
			'abusefilter-parser-warning-' . $this->mExceptionID,
			array_merge( [ $this->mPosition ], $this->mParams )
		);
	}
}

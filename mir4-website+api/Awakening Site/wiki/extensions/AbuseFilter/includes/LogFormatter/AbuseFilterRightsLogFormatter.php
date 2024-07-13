<?php

namespace MediaWiki\Extension\AbuseFilter\LogFormatter;

use LogFormatter;

class AbuseFilterRightsLogFormatter extends LogFormatter {

	/**
	 * This method is identical to the parent, but it's redeclared to give grep a chance
	 * to find the messages.
	 * @inheritDoc
	 */
	protected function getMessageKey() {
		$subtype = $this->entry->getSubtype();
		// Messages that can be used here:
		// * logentry-rights-blockautopromote
		// * logentry-rights-restoreautopromote
		return "logentry-rights-$subtype";
	}

	/**
	 * @inheritDoc
	 */
	protected function extractParameters() {
		$ret = [];
		$ret[3] = $this->entry->getTarget()->getText();
		if ( $this->entry->getSubtype() === 'blockautopromote' ) {
			$parameters = $this->entry->getParameters();
			$duration = $parameters['7::duration'];
			$ret[4] = $this->context->getLanguage()->formatDuration( $duration );
		}
		return $ret;
	}

	/**
	 * @inheritDoc
	 */
	protected function getMessageParameters() {
		$params = parent::getMessageParameters();
		// remove "User:" prefix
		$params[2] = $this->formatParameterValue( 'user-link', $this->entry->getTarget()->getText() );
		return $params;
	}

}

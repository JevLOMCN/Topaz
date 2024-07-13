<?php

namespace MediaWiki\Extension\AbuseFilter\LogFormatter;

use LogFormatter;
use MediaWiki\SpecialPage\SpecialPage;
use Message;

class AbuseFilterModifyLogFormatter extends LogFormatter {

	/**
	 * @return string
	 */
	protected function getMessageKey() {
		$subtype = $this->entry->getSubtype();
		// Messages that can be used here:
		// * abusefilter-logentry-create
		// * abusefilter-logentry-modify
		return "abusefilter-logentry-$subtype";
	}

	/**
	 * @return array
	 */
	protected function extractParameters() {
		$parameters = $this->entry->getParameters();
		if ( $this->entry->isLegacy() ) {
			[ $historyId, $filterId ] = $parameters;
		} else {
			$historyId = $parameters['historyId'];
			$filterId = $parameters['newId'];
		}

		$detailsTitle = SpecialPage::getTitleFor(
			'AbuseFilter',
			"history/$filterId/diff/prev/$historyId"
		);

		$params = [];
		$params[3] = Message::rawParam(
			$this->makePageLink(
				$this->entry->getTarget(),
				[],
				$this->msg( 'abusefilter-log-detailedentry-local' )
					->numParams( $filterId )->escaped()
			)
		);
		$params[4] = Message::rawParam(
			$this->makePageLink(
				$detailsTitle,
				[],
				$this->msg( 'abusefilter-log-detailslink' )->escaped()
			)
		);

		return $params;
	}

}

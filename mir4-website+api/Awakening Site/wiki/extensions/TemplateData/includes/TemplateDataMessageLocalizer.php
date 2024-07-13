<?php

namespace MediaWiki\Extension\TemplateData;

use Language;
use MessageLocalizer;

/**
 * @license GPL-2.0-or-later
 * @codeCoverageIgnore Trivial facade
 */
class TemplateDataMessageLocalizer implements MessageLocalizer {

	private Language $language;

	public function __construct( Language $language ) {
		$this->language = $language;
	}

	/** @inheritDoc */
	public function msg( $key, ...$params ) {
		return wfMessage( $key, ...$params )->inLanguage( $this->language );
	}

}

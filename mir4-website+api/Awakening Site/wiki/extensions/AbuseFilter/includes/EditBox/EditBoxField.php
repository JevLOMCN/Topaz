<?php

namespace MediaWiki\Extension\AbuseFilter\EditBox;

use HTMLFormField;

/**
 * This class is used to easily wrap a filter editor box inside an HTMLForm. For now it's just a transparent
 * wrapper around the given HTML string. In the future, some of the actual logic might be moved here.
 * @unstable
 */
class EditBoxField extends HTMLFormField {
	/** @var string */
	private $html;

	/**
	 * @param array $params
	 */
	public function __construct( array $params ) {
		parent::__construct( $params );
		$this->html = $params['html'];
	}

	/**
	 * @inheritDoc
	 */
	public function getInputHTML( $value ): string {
		return $this->html;
	}
}

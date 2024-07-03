<?php

namespace OOUI;

/**
 * Input widget with a text field.
 */
class SearchInputWidget extends TextInputWidget {

	/**
	 * @param array $config
	 */
	public function __construct( array $config = [] ) {
		// Config initialization
		$config = array_merge( [
			'icon' => 'search',
		], $config );

		// Parent constructor
		parent::__construct( $config );
	}

	/** @inheritDoc */
	protected function getValidType( $config ) {
		return 'search';
	}
}

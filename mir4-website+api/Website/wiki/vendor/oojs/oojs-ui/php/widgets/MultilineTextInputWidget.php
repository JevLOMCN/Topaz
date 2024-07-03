<?php

namespace OOUI;

/**
 * Input widget with a text field.
 */
class MultilineTextInputWidget extends TextInputWidget {

	/**
	 * Allow multiple lines of text.
	 *
	 * @var bool
	 */
	protected $multiline = true;

	/**
	 * @param array $config Configuration options
	 *      - int $config['rows'] Number of visible lines in textarea.
	 */
	public function __construct( array $config = [] ) {
		// Config initialization
		$config = array_merge( [
			'readOnly' => false,
			'autofocus' => false,
			'required' => false,
		], $config );

		// Parent constructor
		parent::__construct( $config );

		if ( $config['rows'] ?? null ) {
			$this->input->setAttributes( [ 'rows' => $config['rows'] ] );
		}
	}

	/** @inheritDoc */
	protected function getInputElement( $config ) {
		return new Tag( 'textarea' );
	}

	/** @inheritDoc */
	public function getConfig( &$config ) {
		$rows = $this->input->getAttribute( 'rows' );
		if ( $rows !== null ) {
			$config['rows'] = $rows;
		}
		return parent::getConfig( $config );
	}
}

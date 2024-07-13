<?php

namespace OOUI;

/**
 * Data widget intended for creating 'hidden'-type inputs.
 */
class HiddenInputWidget extends Widget {

	/**
	 * @var string
	 */
	public static $tagName = 'input';

	/**
	 * DataWidget constructor.
	 *
	 * @param array $config Configuration options
	 *      - string $config['value'] The data the input contains. (default: '')
	 *      - string $config['name'] The name of the hidden input. (default: '')
	 */
	public function __construct( array $config ) {
		// Parent constructor
		parent::__construct( $config );

		// Initialization
		$this->setAttributes( [
			'type' => 'hidden',
			'value' => $config['value'] ?? '',
			'name' => $config['name'] ?? '',
		] );
		$this->removeAttributes( [ 'aria-disabled' ] );
	}

	/** @inheritDoc */
	public function getConfig( &$config ) {
		$config['value'] = $this->getAttribute( 'value' );
		$config['name'] = $this->getAttribute( 'name' );
		return parent::getConfig( $config );
	}
}

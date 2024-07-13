<?php

namespace OOUI;

/**
 * Group widget for multiple related buttons.
 *
 * Use together with ButtonWidget.
 */
class ButtonGroupWidget extends Widget {
	use GroupElement;
	use TitledElement;

	/* Static Properties */

	/** @var string */
	public static $tagName = 'span';

	/**
	 * @param array $config Configuration options
	 *      - ButtonWidget[] $config['items'] Buttons to add
	 */
	public function __construct( array $config = [] ) {
		// Parent constructor
		parent::__construct( $config );

		// Traits
		$this->initializeGroupElement( array_merge( [ 'group' => $this ], $config ) );
		$this->initializeTitledElement( $config );

		// Initialization
		$this->addClasses( [ 'oo-ui-buttonGroupWidget' ] );
		if ( isset( $config['items'] ) ) {
			$this->addItems( $config['items'] );
		}
	}
}

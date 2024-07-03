<?php

namespace OOUI;

/**
 * Indicator widget.
 *
 * See IndicatorElement for more information.
 */
class IndicatorWidget extends Widget {
	use IndicatorElement;
	use TitledElement;
	use LabelElement;

	/* Static Properties */

	/** @var string */
	public static $tagName = 'span';

	/**
	 * @param array $config Configuration options
	 */
	public function __construct( array $config = [] ) {
		// Parent constructor
		parent::__construct( $config );

		// Traits
		$this->initializeIndicatorElement(
			array_merge( [ 'indicatorElement' => $this ], $config )
		);
		$this->initializeTitledElement(
			array_merge( [ 'titled' => $this ], $config )
		);
		$this->initializeLabelElement(
			array_merge( [ 'labelElement' => $this, 'invisibleLabel' => true ], $config )
		);

		// Initialization
		$this->addClasses( [ 'oo-ui-indicatorWidget' ] );
		// Remove class added by LabelElement initialization. It causes unexpected CSS to apply when
		// nested in other widgets, because this widget used to not mix in LabelElement.
		$this->removeClasses( [ 'oo-ui-labelElement-label' ] );

		$this->registerConfigCallback( function ( &$config ) {
			// We have changed the default value, so change when it is outputted.
			unset( $config['invisibleLabel'] );
			if ( $this->invisibleLabel !== true ) {
				$config['invisibleLabel'] = $this->invisibleLabel;
			}
		} );
	}
}

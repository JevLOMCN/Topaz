<?php

namespace OOUI;

/**
 * Icon widget.
 *
 * See IconElement for more information.
 */
class IconWidget extends Widget {
	use IconElement;
	use TitledElement;
	use LabelElement;
	use FlaggedElement;

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
		$this->initializeIconElement(
			array_merge( [ 'iconElement' => $this ], $config )
		);
		$this->initializeTitledElement(
			array_merge( [ 'titled' => $this ], $config )
		);
		$this->initializeLabelElement(
			array_merge( [ 'labelElement' => $this, 'invisibleLabel' => true ], $config )
		);
		$this->initializeFlaggedElement(
			array_merge( [ 'flagged' => $this ], $config )
		);

		// Initialization
		$this->addClasses( [ 'oo-ui-iconWidget' ] );
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

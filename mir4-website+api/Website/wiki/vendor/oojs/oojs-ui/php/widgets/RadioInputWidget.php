<?php

namespace OOUI;

/**
 * Radio input widget.
 */
class RadioInputWidget extends InputWidget {
	use RequiredElement;

	/* Static Properties */

	/** @var string */
	public static $tagName = 'span';

	/**
	 * @param array $config Configuration options
	 *      - bool $config['selected'] Whether the radio button is initially selected
	 *          (default: false)
	 */
	public function __construct( array $config = [] ) {
		// Parent constructor
		parent::__construct( $config );

		// Traits
		$this->initializeRequiredElement(
			array_merge( [ 'indicatorElement' => null ], $config )
		);

		// Initialization
		$this->addClasses( [ 'oo-ui-radioInputWidget' ] );
		// Required for pretty styling in WikimediaUI theme
		$this->appendContent( new Tag( 'span' ) );
		$this->setSelected( $config['selected'] ?? false );
	}

	/** @inheritDoc */
	protected function getInputElement( $config ) {
		return ( new Tag( 'input' ) )->setAttributes( [ 'type' => 'radio' ] );
	}

	/**
	 * Set selection state of this radio button.
	 *
	 * @param bool $state Whether the button is selected
	 * @return $this
	 */
	public function setSelected( $state ) {
		// RadioInputWidget doesn't track its state.
		if ( $state ) {
			$this->input->setAttributes( [ 'checked' => 'checked' ] );
		} else {
			$this->input->removeAttributes( [ 'checked' ] );
		}
		return $this;
	}

	/**
	 * Check if this radio button is selected.
	 *
	 * @return bool Radio is selected
	 */
	public function isSelected() {
		return $this->input->getAttribute( 'checked' ) === 'checked';
	}

	/** @inheritDoc */
	public function getConfig( &$config ) {
		if ( $this->isSelected() ) {
			$config['selected'] = true;
		}
		return parent::getConfig( $config );
	}
}

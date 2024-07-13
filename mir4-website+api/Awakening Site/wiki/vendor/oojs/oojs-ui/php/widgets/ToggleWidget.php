<?php

namespace OOUI;

/**
 * Toggle widget.
 */
class ToggleWidget extends Widget {
	use TitledElement;

	/**
	 * Defines the value of the widget
	 *
	 * @var bool
	 */
	protected $value;

	public function __construct( array $config = [] ) {
		parent::__construct( $config );

		// Traits
		$this->initializeTitledElement( $config );

		$this->addClasses( [ 'oo-ui-toggleWidget' ] );
		$this->setValue( $config['value'] ?? false );
	}

	/** @inheritDoc */
	public function getConfig( &$config ) {
		$config['value'] = $this->value;
		return parent::getConfig( $config );
	}

	/**
	 * Get value representing toggle state.
	 *
	 * @return bool value The on/off state
	 */
	public function getValue() {
		return $this->value;
	}

	/**
	 * Set the value of the widget
	 *
	 * @param bool|null $value Widget
	 * @return $this
	 */
	public function setValue( $value = null ) {
		$this->value = (bool)$value;

		$this->toggleClasses( [ 'oo-ui-toggleWidget-on' ], $this->value );
		$this->toggleClasses( [ 'oo-ui-toggleWidget-off' ], !$this->value );

		return $this;
	}
}

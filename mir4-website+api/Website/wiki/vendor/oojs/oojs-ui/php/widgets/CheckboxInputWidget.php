<?php

namespace OOUI;

/**
 * Checkbox input widget.
 */
class CheckboxInputWidget extends InputWidget {
	use RequiredElement;

	/* Static Properties */

	/** @var string */
	public static $tagName = 'span';

	/* Properties */

	/**
	 * Whether the checkbox is selected.
	 *
	 * @var bool
	 */
	protected $selected;

	/**
	 * @var bool
	 */
	protected $indeterminate;

	/**
	 * @var IconWidget
	 */
	protected $checkIcon;

	/**
	 * @param array $config Configuration options
	 *      - bool $config['selected'] Whether the checkbox is initially selected
	 *          (default: false)
	 *      - bool $config['indeterminate'] Whether the checkbox is in the indeterminate state.
	 *          (default: false)
	 */
	public function __construct( array $config = [] ) {
		// Parent constructor
		parent::__construct( $config );

		// Properties
		$this->checkIcon = new IconWidget( [
			'icon' => 'check',
			'classes' => [ 'oo-ui-checkboxInputWidget-checkIcon' ],
		] );

		// Traits
		$this->initializeRequiredElement(
			array_merge( [ 'indicatorElement' => null ], $config )
		);

		// Initialization
		$this->addClasses( [ 'oo-ui-checkboxInputWidget' ] );
		// Required for pretty styling in WikimediaUI theme
		$this->appendContent( $this->checkIcon );
		$this->setSelected( $config['selected'] ?? false );
		$this->setIndeterminate( $config['indeterminate'] ?? false );
	}

	/** @inheritDoc */
	protected function getInputElement( $config ) {
		return ( new Tag( 'input' ) )->setAttributes( [ 'type' => 'checkbox' ] );
	}

	/**
	 * Set selection state of this checkbox.
	 *
	 * @param bool $state Whether the checkbox is selected
	 * @return $this
	 */
	public function setSelected( $state ) {
		$this->selected = (bool)$state;
		if ( $this->selected ) {
			$this->input->setAttributes( [ 'checked' => 'checked' ] );
		} else {
			$this->input->removeAttributes( [ 'checked' ] );
		}
		return $this;
	}

	/**
	 * Check if this checkbox is selected.
	 *
	 * @return bool Checkbox is selected
	 */
	public function isSelected() {
		return $this->selected;
	}

	/**
	 * Set indeterminate state of this checkbox.
	 *
	 * @param bool $state Whether the checkbox is indeterminate
	 * @return $this
	 */
	public function setIndeterminate( $state ) {
		$this->indeterminate = (bool)$state;
		// NB: A checkbox can't be made indeterminate through HTML,
		// there is no indeterminate attribute. We can only store
		// the state for infusion.
		return $this;
	}

	/**
	 * Check if this checkbox is indeterminate.
	 *
	 * @return bool Checkbox is indeterminate
	 */
	public function isIndeterminate() {
		return $this->indeterminate;
	}

	/** @inheritDoc */
	public function getConfig( &$config ) {
		if ( $this->selected ) {
			$config['selected'] = $this->selected;
		}
		if ( $this->indeterminate ) {
			$config['indeterminate'] = $this->indeterminate;
		}
		return parent::getConfig( $config );
	}
}

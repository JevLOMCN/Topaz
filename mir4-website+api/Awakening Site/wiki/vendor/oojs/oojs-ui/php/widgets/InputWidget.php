<?php

namespace OOUI;

/**
 * Base class for input widgets.
 *
 * @abstract
 */
class InputWidget extends Widget {
	use TabIndexedElement;
	use TitledElement;
	use AccessKeyedElement;

	/* Properties */

	/**
	 * Input element.
	 *
	 * @var Tag
	 */
	protected $input;

	/**
	 * Input value.
	 *
	 * @var string
	 */
	protected $value = '';

	/**
	 * @param array $config Configuration options
	 *      - string $config['name'] HTML input name (default: '')
	 *      - string $config['value'] Input value (default: '')
	 *      - string $config['dir'] The directionality of the input (ltr/rtl)
	 *      - string $config['inputId'] The value of the input’s HTML `id` attribute.
	 */
	public function __construct( array $config = [] ) {
		// Parent constructor
		parent::__construct( $config );

		// Properties
		$this->input = $this->getInputElement( $config );

		// Traits
		$this->initializeTabIndexedElement(
			array_merge( [ 'tabIndexed' => $this->input ], $config )
		);
		$this->initializeTitledElement(
			array_merge( [ 'titled' => $this->input ], $config )
		);
		$this->initializeAccessKeyedElement(
			array_merge( [ 'accessKeyed' => $this->input ], $config )
		);

		// Initialization
		if ( isset( $config['name'] ) ) {
			$this->input->setAttributes( [ 'name' => $config['name'] ] );
		}
		if ( $this->isDisabled() ) {
			$this->input->setAttributes( [ 'disabled' => 'disabled' ] );
		}
		$this
			->addClasses( [ 'oo-ui-inputWidget' ] )
			->appendContent( $this->input );
		$this->input->addClasses( [ 'oo-ui-inputWidget-input' ] );
		$this->setValue( $config['value'] ?? null );
		if ( isset( $config['dir'] ) ) {
			$this->setDir( $config['dir'] );
		}
		if ( isset( $config['inputId'] ) ) {
			$this->setInputId( $config['inputId'] );
		}
	}

	/**
	 * Get input element.
	 *
	 * @param array $config Configuration options
	 * @return Tag Input element
	 */
	protected function getInputElement( $config ) {
		return new Tag( 'input' );
	}

	/**
	 * Get the value of the input.
	 *
	 * @return string Input value
	 */
	public function getValue() {
		return $this->value;
	}

	/**
	 * Set the directionality of the input.
	 *
	 * @param string $dir Text directionality: 'ltr', 'rtl' or 'auto'
	 * @return $this
	 */
	public function setDir( $dir ) {
		$this->input->setAttributes( [ 'dir' => $dir ] );
		return $this;
	}

	/**
	 * Set the value of the input.
	 *
	 * @param mixed $value New value should usually be a string
	 * @return $this
	 */
	public function setValue( $value ) {
		$this->value = $this->cleanUpValue( $value );
		$this->input->setValue( $this->value );
		return $this;
	}

	/**
	 * Clean up incoming value.
	 *
	 * Ensures value is a string, and converts null to empty string.
	 *
	 * @param mixed $value Original value
	 * @return string Cleaned up value
	 */
	protected function cleanUpValue( $value ) {
		if ( $value === null ) {
			return '';
		} else {
			return (string)$value;
		}
	}

	/** @inheritDoc */
	public function setDisabled( $disabled ) {
		parent::setDisabled( $disabled );
		if ( isset( $this->input ) ) {
			if ( $this->isDisabled() ) {
				$this->input->setAttributes( [ 'disabled' => 'disabled' ] );
			} else {
				$this->input->removeAttributes( [ 'disabled' ] );
			}
		}
		return $this;
	}

	/**
	 * Set the 'id' attribute of the `<input>` element.
	 *
	 * @param string $id The ID of the input element
	 * @return $this
	 */
	public function setInputId( $id ) {
		$this->input->setAttributes( [ 'id' => $id ] );
		return $this;
	}

	/** @inheritDoc */
	public function getConfig( &$config ) {
		$name = $this->input->getAttribute( 'name' );
		if ( $name !== null ) {
			$config['name'] = $name;
		}
		if ( $this->value !== '' ) {
			$config['value'] = $this->value;
		}
		$dir = $this->input->getAttribute( 'dir' );
		if ( $dir !== null ) {
			$config['dir'] = $dir;
		}
		$id = $this->input->getAttribute( 'id' );
		if ( $id !== null ) {
			$config['inputId'] = $id;
		}
		return parent::getConfig( $config );
	}
}

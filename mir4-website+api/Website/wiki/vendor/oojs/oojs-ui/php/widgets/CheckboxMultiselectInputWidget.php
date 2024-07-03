<?php

namespace OOUI;

/**
 * Multiple checkbox input widget. Intended to be used within a OO.ui.FormLayout.
 *
 * @phan-suppress-next-line PhanParamSignatureMismatch Parent returns a string
 * @method string[] getValue()
 */
class CheckboxMultiselectInputWidget extends InputWidget {

	/* Properties */

	/**
	 * @var string|null
	 */
	protected $name = null;

	/**
	 * Input value.
	 *
	 * @var string[]
	 */
	protected $value = [];

	/**
	 * Layouts for this input, as FieldLayouts.
	 *
	 * @var FieldLayout[]
	 */
	protected $fields = [];

	/**
	 * @param array $config Configuration options
	 *      - array[] $config['options'] Array of menu options in the format
	 *          `[ 'data' => …, 'label' => …, 'disabled' => … ]`
	 */
	public function __construct( array $config = [] ) {
		// Parent constructor
		parent::__construct( $config );

		if ( isset( $config['name'] ) ) {
			$this->name = $config['name'];
		}

		// Initialization
		$this->setOptions( $config['options'] ?? [] );
		// Have to repeat this from parent, as we need options to be set up for this to make sense
		$this->setValue( $config['value'] ?? null );
		$this->addClasses( [ 'oo-ui-checkboxMultiselectInputWidget' ] );
	}

	/** @inheritDoc */
	protected function getInputElement( $config ) {
		// Actually unused
		return new Tag( 'unused' );
	}

	/**
	 * Set the value of the input.
	 *
	 * @param mixed $value New value should be an array of strings
	 * @return $this
	 */
	public function setValue( $value ) {
		$this->value = $this->cleanUpValue( $value );
		// Deselect all options
		foreach ( $this->fields as $field ) {
			$widget = $field->getField();
			'@phan-var CheckboxInputWidget $widget';
			$widget->setSelected( false );
		}
		// Select the requested ones
		foreach ( $this->value as $key ) {
			$widget = $this->fields[ $key ]->getField();
			'@phan-var CheckboxInputWidget $widget';
			$widget->setSelected( true );
		}
		return $this;
	}

	/**
	 * Clean up incoming value.
	 *
	 * @param mixed $value Original value
	 * @return string[] Cleaned up value
	 * @suppress PhanParamSignatureMismatch Parent has 'string' instead of 'string[]'
	 */
	protected function cleanUpValue( $value ) {
		$cleanValue = [];
		if ( !is_array( $value ) ) {
			return $cleanValue;
		}
		foreach ( $value as $singleValue ) {
			$singleValue = parent::cleanUpValue( $singleValue );
			// Remove options that we don't have here
			if ( !isset( $this->fields[ $singleValue ] ) ) {
				continue;
			}
			$cleanValue[] = $singleValue;
		}
		return $cleanValue;
	}

	/**
	 * Set the options available for this input.
	 *
	 * @param array[] $options Array of menu options in the format
	 *   `[ 'data' => …, 'label' => …, 'disabled' => … ]`
	 * @return $this
	 */
	public function setOptions( $options ) {
		$this->fields = [];

		// Rebuild the checkboxes
		$this->clearContent();
		$name = $this->name;
		foreach ( $options as $opt ) {
			$optValue = parent::cleanUpValue( $opt['data'] );
			$optDisabled = $opt['disabled'] ?? false;
			$field = new FieldLayout(
				new CheckboxInputWidget( [
					'name' => $name,
					'value' => $optValue,
					'disabled' => $this->isDisabled() || $optDisabled,
				] ),
				[
					'label' => $opt['label'] ?? $optValue,
					'align' => 'inline',
				]
			);

			$this->fields[ $optValue ] = $field;
			$this->appendContent( $field );
		}

		// Re-set the value, checking the checkboxes as needed.
		// This will also get rid of any stale options that we just removed.
		$this->setValue( $this->getValue() );

		return $this;
	}

	/** @inheritDoc */
	public function setDisabled( $disabled ) {
		parent::setDisabled( $disabled );
		foreach ( $this->fields as $field ) {
			$field->getField()->setDisabled( $this->isDisabled() );
		}
		return $this;
	}

	/** @inheritDoc */
	public function getConfig( &$config ) {
		$options = [];
		foreach ( $this->fields as $field ) {
			$widget = $field->getField();
			'@phan-var CheckboxInputWidget $widget';
			$options[] = [
				'data' => $widget->getValue(),
				'label' => $field->getLabel(),
				'disabled' => $widget->isDisabled(),
			];
		}
		$config['options'] = $options;
		return parent::getConfig( $config );
	}
}

<?php

namespace OOUI;

/**
 * Combo box input widget, wrapping a text input with `<datalist>`. Intended to be used within a
 * OO.ui.FormLayout.
 */
class ComboBoxInputWidget extends TextInputWidget {
	/**
	 * HTML `<option>` tags for this widget, as Tags.
	 * @var array
	 */
	protected $options = [];

	/** @var bool */
	protected $forceAutocomplete;
	/** @var Widget */
	protected $downIndicator;
	/** @var Tag */
	protected $datalist;

	/**
	 * @param array $config Configuration options
	 *      - array[] $config['options'] Array of menu options in the format
	 *          `[ 'data' => …, 'label' => … ]`
	 */
	public function __construct( array $config = [] ) {
		// Parent constructor
		parent::__construct( $config );

		// Initialization
		$this->forceAutocomplete = $config['autocomplete'] ?? false;
		$this->downIndicator = new IndicatorWidget( [ 'indicator' => 'down' ] );
		$this->datalist = new Tag( 'datalist' );
		$this->datalist->setAttributes( [ 'id' => Tag::generateElementId() ] );
		$this->input->setAttributes( [ 'list' => $this->datalist->getAttribute( 'id' ) ] );

		$this->setOptions( $config['options'] ?? [] );
		$this->addClasses( [ 'oo-ui-comboBoxInputWidget', 'oo-ui-comboBoxInputWidget-php' ] );
		$this->appendContent( $this->downIndicator, $this->datalist );
	}

	/**
	 * Set the options available for this input.
	 *
	 * @param array[] $options Array of menu options in the format
	 *   `[ 'data' => …, 'label' => … ]`
	 * @return $this
	 */
	public function setOptions( $options ) {
		$this->options = [];

		$this->datalist->clearContent();
		foreach ( $options as $opt ) {
			$option = ( new Tag( 'option' ) )
				->setAttributes( [ 'value' => $opt['data'] ] )
				->appendContent( $opt['label'] ?? $opt['data'] );

			$this->options[] = $option;
			$this->datalist->appendContent( $option );
		}

		return $this;
	}

	/** @inheritDoc */
	public function getConfig( &$config ) {
		$options = [];
		foreach ( $this->options as $option ) {
			$options[] = [
				'data' => $option->getAttribute( 'value' ),
				'label' => $option->content[0],
			];
		}
		$config['options'] = $options;
		// JS ComboBoxInputWidget has `autocomplete: false` in the defaults. Make sure
		// explicitly passing `autocomplete: true` overrides that. Doing so doesn't make
		// much sense, this is just to make the tests happy.
		if ( $this->forceAutocomplete ) {
			$config['autocomplete'] = true;
		}
		$config['$overlay'] = true;
		return parent::getConfig( $config );
	}
}

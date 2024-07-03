<?php

namespace OOUI;

/**
 * Input widget with a number field.
 */
class NumberInputWidget extends TextInputWidget {

	/** @var float|null */
	protected $buttonStep;
	/** @var float|null */
	protected $pageStep;
	/** @var bool|null */
	protected $showButtons;

	/**
	 * @param array $config Configuration options
	 *      - float $config['min'] Minimum input allowed
	 *      - float $config['max'] Maximum input allowed
	 *      - float|null $config['step'] If specified, the field only accepts values that are
	 *          multiples of this. (default: null)
	 *      - float $config['buttonStep'] Delta when using the buttons or Up/Down arrow keys.
	 *          Defaults to `step` if specified, otherwise `1`.
	 *      - float $config['pageStep'] Delta when using the Page-up/Page-down keys.
	 *          Defaults to 10 times `buttonStep`.
	 *      - bool $config['showButtons'] Show increment and decrement buttons (default: true)
	 */
	public function __construct( array $config = [] ) {
		$config['type'] = 'number';

		// Parent constructor
		parent::__construct( $config );

		if ( isset( $config['min'] ) ) {
			$this->input->setAttributes( [ 'min' => $config['min'] ] );
		}

		if ( isset( $config['max'] ) ) {
			$this->input->setAttributes( [ 'max' => $config['max'] ] );
		}

		$this->input->setAttributes( [ 'step' => $config['step'] ?? 'any' ] );

		if ( isset( $config['buttonStep'] ) ) {
			$this->buttonStep = $config['buttonStep'];
		}
		if ( isset( $config['pageStep'] ) ) {
			$this->pageStep = $config['pageStep'];
		}
		if ( isset( $config['showButtons'] ) ) {
			$this->showButtons = $config['showButtons'];
		}

		$this->addClasses( [
			'oo-ui-numberInputWidget',
			'oo-ui-numberInputWidget-php',
		] );
	}

	/** @inheritDoc */
	public function getConfig( &$config ) {
		$min = $this->input->getAttribute( 'min' );
		if ( $min !== null ) {
			$config['min'] = $min;
		}
		$max = $this->input->getAttribute( 'max' );
		if ( $max !== null ) {
			$config['max'] = $max;
		}
		$step = $this->input->getAttribute( 'step' );
		if ( $step !== 'any' ) {
			$config['step'] = $step;
		}
		if ( $this->pageStep !== null ) {
			$config['pageStep'] = $this->pageStep;
		}
		if ( $this->buttonStep !== null ) {
			$config['buttonStep'] = $this->buttonStep;
		}
		if ( $this->showButtons !== null ) {
			$config['showButtons'] = $this->showButtons;
		}
		return parent::getConfig( $config );
	}
}

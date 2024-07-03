<?php

namespace OOUI;

/**
 * Element with a required attribute.
 *
 * @abstract
 */
trait RequiredElement {
	/**
	 * Mark as required.
	 *
	 * @var bool
	 */
	protected $required = false;

	/**
	 * @var Element
	 */
	protected $requiredElement;

	/**
	 * @var IndicatorElement|null
	 */
	protected $indicatorElement;

	/**
	 * @var Element|null
	 */
	protected $input;

	/**
	 * @param array $config Configuration options
	 *      - bool $config['required'] Mark the field as required.
	 *          Implies `indicator: 'required'`. Note that `false` & setting `indicator: 'required'
	 *          will result in no indicator shown. (default: false)
	 *      - Element $config['requiredElement']
	 *      - IndicatorElement $config['indicatorElement']
	 */
	public function initializeRequiredElement( array $config = [] ) {
		// Properties
		$this->requiredElement = $config['requiredElement'] ?? ( $this->input ?? $this );
		$this->indicatorElement = array_key_exists( 'indicatorElement', $config ) ? $config['indicatorElement'] : $this;

		if ( $this->indicatorElement && !method_exists( $this->indicatorElement, 'getIndicator' ) ) {
			throw new Exception( 'config[\'indicatorElement\'] must use the IndicatorElement trait.' );
		}

		// Initialization
		$this->setRequired( $config['required'] ?? false );

		$this->registerConfigCallback( function ( &$config ) {
			$config['required'] = $this->required;
		} );
	}

	/**
	 * Check if the widget is required.
	 *
	 * @return bool
	 */
	public function isRequired(): bool {
		return $this->required;
	}

	/**
	 * Set the required state of the widget.
	 *
	 * @param bool $state Make input required
	 * @return $this
	 */
	public function setRequired( bool $state ) {
		$this->required = $state;
		if ( $this->required ) {
			$this->requiredElement->setAttributes( [
				'required' => null
			] );
			if ( $this->indicatorElement && $this->indicatorElement->getIndicator() === null ) {
				$this->indicatorElement->setIndicator( 'required' );
			}
		} else {
			$this->requiredElement->removeAttributes( [
				'required'
			] );
			if ( $this->indicatorElement && $this->indicatorElement->getIndicator() === 'required' ) {
				$this->indicatorElement->setIndicator( null );
			}
		}
		return $this;
	}

	/**
	 * @param callable $func
	 */
	abstract public function registerConfigCallback( callable $func );
}

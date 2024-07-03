<?php

namespace OOUI;

/**
 * Element containing an indicator.
 *
 * Indicators are graphics, smaller than normal text. They can be used to describe unique status or
 * behavior. Indicators should only be used in exceptional cases; such as a button that opens a menu
 * instead of performing an action directly, or an item in a list which has errors that need to be
 * resolved.
 *
 * @abstract
 */
trait IndicatorElement {
	/**
	 * Symbolic indicator name
	 *
	 * @var string|null
	 */
	protected $indicatorName = null;

	/**
	 * @var Tag
	 */
	protected $indicator;

	/**
	 * @param array $config Configuration options
	 *      - string $config['indicator'] Symbolic indicator name
	 */
	public function initializeIndicatorElement( array $config = [] ) {
		// Properties
		// FIXME 'indicatorElement' is a very stupid way to call '$indicator'
		$this->indicator = $config['indicatorElement'] ?? new Tag( 'span' );

		// Initialization
		$this->indicator->addClasses( [ 'oo-ui-indicatorElement-indicator' ] );
		$this->setIndicator( $config['indicator'] ?? null );

		$this->registerConfigCallback( function ( &$config ) {
			if ( $this->indicatorName !== null ) {
				$config['indicator'] = $this->indicatorName;
			}
		} );
	}

	/**
	 * Set indicator name.
	 *
	 * @param string|null $indicator Symbolic name of indicator to use or null for no indicator
	 * @return $this
	 */
	public function setIndicator( $indicator = null ) {
		if ( $this->indicatorName !== null ) {
			$this->indicator->removeClasses( [ 'oo-ui-indicator-' . $this->indicatorName ] );
		}
		if ( $indicator !== null ) {
			$this->indicator->addClasses( [ 'oo-ui-indicator-' . $indicator ] );
		}

		$this->indicatorName = $indicator;
		$this->toggleClasses( [ 'oo-ui-indicatorElement' ], (bool)$this->indicatorName );
		$this->indicator->toggleClasses( [ 'oo-ui-indicatorElement-noIndicator' ],
			!$this->indicatorName );

		return $this;
	}

	/**
	 * Get indicator name.
	 *
	 * @return string|null Symbolic name of indicator, null if not set
	 */
	public function getIndicator() {
		return $this->indicatorName;
	}

	/**
	 * Do not use outside of Theme::updateElementClasses
	 *
	 * @protected
	 * @return Tag
	 */
	public function getIndicatorElement() {
		return $this->indicator;
	}

	/**
	 * Toggle CSS classes.
	 *
	 * @param array $classes List of classes to add
	 * @param bool|null $toggle Add classes
	 * @return $this
	 */
	abstract public function toggleClasses( array $classes, $toggle = null );

	/**
	 * @param callable $func
	 */
	abstract public function registerConfigCallback( callable $func );
}

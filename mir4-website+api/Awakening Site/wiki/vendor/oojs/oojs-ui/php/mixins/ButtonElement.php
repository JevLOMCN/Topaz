<?php

namespace OOUI;

/**
 * Element with a button.
 *
 * Buttons are used for controls which can be clicked. They can be configured to use tab indexing
 * and access keys for accessibility purposes.
 *
 * @abstract
 */
trait ButtonElement {

	/**
	 * Button is framed.
	 *
	 * @var bool
	 */
	protected $framed = false;

	/**
	 * @var Tag
	 */
	protected $button;

	/**
	 * @param array $config Configuration options
	 *      - bool $config['framed'] Render button with a frame (default: true)
	 */
	public function initializeButtonElement( array $config = [] ) {
		// Properties
		if ( !$this instanceof Element ) {
			throw new Exception( "ButtonElement trait can only be used on Element instances" );
		}
		$target = $config['button'] ?? new Tag( 'a' );
		$this->button = $target;

		// Initialization
		$this->addClasses( [ 'oo-ui-buttonElement' ] );
		$this->button->addClasses( [ 'oo-ui-buttonElement-button' ] );
		$this->toggleFramed( $config['framed'] ?? true );

		// Add `role="button"` on `<a>` elements, where it's needed
		if ( strtolower( $this->button->getTag() ) === 'a' ) {
			$this->button->setAttributes( [
				'role' => 'button',
			] );
		}

		$this->registerConfigCallback( function ( &$config ) {
			if ( $this->framed !== true ) {
				$config['framed'] = $this->framed;
			}
		} );
	}

	/**
	 * Toggle frame.
	 *
	 * @param bool|null $framed Make button framed, omit to toggle
	 * @return $this
	 */
	public function toggleFramed( $framed = null ) {
		$this->framed = $framed !== null ? (bool)$framed : !$this->framed;
		$this->toggleClasses( [ 'oo-ui-buttonElement-framed' ], $this->framed );
		$this->toggleClasses( [ 'oo-ui-buttonElement-frameless' ], !$this->framed );
		return $this;
	}

	/**
	 * Check if button has a frame.
	 *
	 * @return bool Button is framed
	 */
	public function isFramed() {
		return $this->framed;
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

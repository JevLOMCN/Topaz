<?php

namespace OOUI;

/**
 * ToggleSwitch widget.
 */
class ToggleSwitchWidget extends ToggleWidget {
	use TabIndexedElement;

	/**
	 * Hyperlink to visit when clicked.
	 *
	 * @var string|null
	 */
	protected $href = null;

	/**
	 * @var Tag|null
	 */
	private $link = null;

	/**
	 * @var Tag
	 */
	private $glow;

	/**
	 * @var Tag
	 */
	private $grip;

	/**
	 * @param array $config Configuration options
	 *      - string $config['href'] href to link toggle to (default: null)
	 */
	public function __construct( array $config = [] ) {
		$this->href = $config['href'] ?? null;
		$this->link = new Tag( 'a' );

		// Parent constructor
		parent::__construct( $config );

		// Traits
		$this->initializeTabIndexedElement( $config );

		$this->glow = ( new Tag( 'span' ) )->setAttributes( [ 'class' => 'oo-ui-toggleSwitchWidget-glow' ] );
		$this->grip = ( new Tag( 'span' ) )->setAttributes( [ 'class' => 'oo-ui-toggleSwitchWidget-grip' ] );

		$this->addClasses( [ 'oo-ui-toggleSwitchWidget' ] );
		$this->setAttributes( [ 'role' => 'switch' ] );

		$this->link->appendContent( $this->grip );
		$this->appendContent( $this->glow, $this->link );
	}

	/** @inheritDoc */
	public function setValue( $value = null ) {
		parent::setValue( $value );
		$this->setAttributes( [
			'aria-checked' => $this->getValue() ? 'true' : 'false'
		] );
		return $this;
	}

	/**
	 * Set hyperlink location.
	 *
	 * @param string|null $href Hyperlink location, null to remove
	 * @return $this
	 */
	public function setHref( ?string $href ): ToggleSwitchWidget {
		$this->href = is_string( $href ) ? $href : null;

		$this->updateHref();

		return $this;
	}

	/**
	 * Update the href attribute, in case of changes to href or disabled
	 * state.
	 *
	 * @return $this
	 */
	public function updateHref() {
		if ( $this->href !== null && !$this->isDisabled() ) {
			$this->link->setAttributes( [ 'href' => $this->href ] );
		}
		return $this;
	}

	/** @inheritDoc */
	public function getConfig( &$config ) {
		if ( $this->href !== null && !$this->isDisabled() ) {
			$config['href'] = $this->href;
		}
		return parent::getConfig( $config );
	}

	/** @inheritDoc */
	public function setDisabled( $disabled ) {
		$this->disabled = (bool)$disabled;

		if ( $this->href !== null && !$this->isDisabled() ) {
			$this->updateHref();
		}

		return parent::setDisabled( $disabled );
	}
}

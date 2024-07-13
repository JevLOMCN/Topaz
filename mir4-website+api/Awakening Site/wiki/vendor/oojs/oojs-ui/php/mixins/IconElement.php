<?php

namespace OOUI;

/**
 * Element containing an icon.
 *
 * Icons are graphics, about the size of normal text. They can be used to aid the user in locating
 * a control or convey information in a more space efficient way. Icons should rarely be used
 * without labels; such as in a toolbar where space is at a premium or within a context where the
 * meaning is very clear to the user.
 *
 * @abstract
 */
trait IconElement {
	/**
	 * Symbolic icon name.
	 *
	 * @var ?string
	 */
	protected $iconName = null;

	/**
	 * @var Tag
	 */
	protected $icon;

	/**
	 * @param array $config Configuration options
	 *      - string $config['icon'] Symbolic icon name
	 */
	public function initializeIconElement( array $config = [] ) {
		// Properties
		// FIXME 'iconElement' is a very stupid way to call '$icon'
		$this->icon = $config['iconElement'] ?? new Tag( 'span' );

		// Initialization
		$this->icon->addClasses( [ 'oo-ui-iconElement-icon' ] );
		$this->setIcon( $config['icon'] ?? null );

		$this->registerConfigCallback( function ( &$config ) {
			if ( $this->iconName !== null ) {
				$config['icon'] = $this->iconName;
			}
		} );
	}

	/**
	 * Set icon name.
	 *
	 * @param string|null $icon Symbolic icon name
	 * @return $this
	 */
	public function setIcon( $icon = null ) {
		if ( $this->iconName !== null ) {
			$this->icon->removeClasses( [ 'oo-ui-icon-' . $this->iconName ] );
		}
		if ( $icon !== null ) {
			$this->icon->addClasses( [ 'oo-ui-icon-' . $icon ] );
		}

		$this->iconName = $icon;
		$this->toggleClasses( [ 'oo-ui-iconElement' ], (bool)$this->iconName );
		$this->icon->toggleClasses( [ 'oo-ui-iconElement-noIcon' ], !$this->iconName );

		return $this;
	}

	/**
	 * Get icon name.
	 *
	 * @return string Icon name
	 */
	public function getIcon() {
		return $this->iconName;
	}

	/**
	 * Do not use outside of Theme::updateElementClasses
	 *
	 * @protected
	 * @return Tag
	 */
	public function getIconElement() {
		return $this->icon;
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

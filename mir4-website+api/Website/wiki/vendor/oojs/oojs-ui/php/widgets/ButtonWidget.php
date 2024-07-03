<?php

namespace OOUI;

/**
 * Generic widget for buttons.
 */
class ButtonWidget extends Widget {
	use ButtonElement;
	use IconElement;
	use IndicatorElement;
	use LabelElement;
	use TitledElement;
	use FlaggedElement;
	use TabIndexedElement;
	use AccessKeyedElement;

	/* Static Properties */

	/** @var string */
	public static $tagName = 'span';

	/* Properties */

	/**
	 * Whether button is active.
	 *
	 * @var bool
	 */
	protected $active = false;

	/**
	 * Hyperlink to visit when clicked.
	 *
	 * @var string
	 */
	protected $href = null;

	/**
	 * Target to open hyperlink in.
	 *
	 * @var string
	 */
	protected $target = null;

	/**
	 * Search engine traversal hint.
	 *
	 * True if search engines should avoid following this hyperlink.
	 *
	 * @var bool
	 */
	protected $noFollow = true;

	/**
	 * Relationship attributes, such as the noFollow field above, or noopener for the hyperlink.
	 *
	 * @var string[]
	 */
	protected $rel = [];

	/**
	 * @param array $config Configuration options
	 *      - bool $config['active'] Whether button should be shown as active (default: false)
	 *      - string $config['href'] Hyperlink to visit when clicked
	 *      - string $config['target'] Target to open hyperlink in
	 *      - bool $config['noFollow'] Search engine traversal hint (default: true)
	 *      - string|string[] $config['rel'] Relationship attributes for the hyperlink
	 */
	public function __construct( array $config = [] ) {
		// Parent constructor
		parent::__construct( $config );

		// Traits
		$this->initializeButtonElement( $config );
		$this->initializeIconElement( $config );
		$this->initializeIndicatorElement( $config );
		$this->initializeLabelElement( $config );
		$this->initializeTitledElement(
			array_merge( [ 'titled' => $this->button ], $config )
		);
		$this->initializeFlaggedElement( $config );
		$this->initializeTabIndexedElement(
			array_merge( [ 'tabIndexed' => $this->button ], $config )
		);
		$this->initializeAccessKeyedElement(
			array_merge( [ 'accessKeyed' => $this->button ], $config )
		);

		// Initialization
		$this->button->appendContent( $this->icon, $this->label, $this->indicator );
		$this
			->addClasses( [ 'oo-ui-buttonWidget' ] )
			->appendContent( $this->button );

		$this->setActive( $config['active'] ?? false );
		$this->setHref( $config['href'] ?? null );
		$this->setTarget( $config['target'] ?? null );
		$rel = [ 'nofollow' ];
		if ( isset( $config['rel'] ) ) {
			$rel = $config['rel'];
		} elseif ( isset( $config[ 'noFollow' ] ) && $config[ 'noFollow' ] === false ) {
			$rel = [];
		}

		$this->setRel( $rel );
	}

	/**
	 * Get hyperlink location.
	 *
	 * @return string Hyperlink location
	 */
	public function getHref() {
		return $this->href;
	}

	/**
	 * Get hyperlink target.
	 *
	 * @return string Hyperlink target
	 */
	public function getTarget() {
		return $this->target;
	}

	/**
	 * Get search engine traversal hint.
	 *
	 * @return bool Whether search engines should avoid traversing this hyperlink
	 */
	public function getNoFollow() {
		return $this->noFollow;
	}

	/**
	 * Get the relationship attribute of the hyperlink.
	 *
	 * @return string[] Relationship attributes that apply to the hyperlink
	 */
	public function getRel() {
		return $this->rel;
	}

	/**
	 * Set hyperlink location.
	 *
	 * @param string|null $href Hyperlink location, null to remove
	 * @return $this
	 */
	public function setHref( $href ) {
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
			$this->button->setAttributes( [ 'href' => $this->href ] );
		} else {
			$this->button->removeAttributes( [ 'href' ] );
		}
		return $this;
	}

	/**
	 * Set hyperlink target.
	 *
	 * @param string|null $target Hyperlink target, null to remove
	 * @return $this
	 */
	public function setTarget( $target ) {
		$this->target = is_string( $target ) ? $target : null;

		if ( $this->target !== null ) {
			$this->button->setAttributes( [ 'target' => $target ] );
		} else {
			$this->button->removeAttributes( [ 'target' ] );
		}

		return $this;
	}

	/**
	 * Set search engine traversal hint.
	 *
	 * @param bool $noFollow True if search engines should avoid traversing this hyperlink
	 * @return $this
	 */
	public function setNoFollow( $noFollow ) {
		if ( $noFollow !== $this->noFollow ) {
			if ( $noFollow ) {
				$rel = array_merge( $this->rel, [ 'nofollow' ] );
			} else {
				$rel = array_diff( $this->rel, [ 'nofollow' ] );
			}
			$this->setRel( $rel );
		}

		return $this;
	}

	/**
	 * Set the relationship attribute of the hyperlink.
	 *
	 * @param string|string[] $rel Relationship attributes for the hyperlink
	 * @return $this
	 */
	public function setRel( $rel ) {
		$this->rel = $rel === '' ? [] : (array)$rel;
		// For backwards compatibility
		$this->noFollow = in_array( 'nofollow', $this->rel );

		$value = implode( ' ', $this->rel );
		if ( $value !== '' ) {
			$this->button->setAttributes( [ 'rel' => $value ] );
		} else {
			$this->button->removeAttributes( [ 'rel' ] );
		}

		return $this;
	}

	/**
	 * Toggle active state.
	 *
	 * A button should be marked as active when clicking it would only refresh the page.
	 *
	 * @param bool|null $active Make button active
	 * @return $this
	 */
	public function setActive( $active = null ) {
		$this->active = (bool)$active;
		$this->toggleClasses( [ 'oo-ui-buttonElement-active' ], $this->active );
		return $this;
	}

	/**
	 * Check if button is active.
	 *
	 * @return bool Button is active
	 */
	public function isActive() {
		return $this->active;
	}

	/** @inheritDoc */
	public function getConfig( &$config ) {
		if ( $this->active !== false ) {
			$config['active'] = $this->active;
		}
		if ( $this->href !== null ) {
			$config['href'] = $this->href;
		}
		if ( $this->target !== null ) {
			$config['target'] = $this->target;
		}
		if ( $this->noFollow !== true ) {
			$config['noFollow'] = $this->noFollow;
		}
		if ( $this->rel !== [] ) {
			$config['rel'] = $this->rel;
		}
		return parent::getConfig( $config );
	}
}

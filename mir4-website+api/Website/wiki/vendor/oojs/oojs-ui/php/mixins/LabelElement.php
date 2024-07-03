<?php

namespace OOUI;

/**
 * Element containing a label.
 *
 * @abstract
 */
trait LabelElement {
	/**
	 * Label value.
	 *
	 * @var string|HtmlSnippet|null
	 */
	protected $labelValue = null;

	/**
	 * Label value.
	 *
	 * @var bool
	 */
	protected $invisibleLabel = false;

	/**
	 * @var Tag
	 */
	protected $label;

	/**
	 * @param array $config Configuration options
	 *      - string|HtmlSnippet $config['label'] Label text
	 *      - bool $config['invisibleLabel'] Whether the label should be visually hidden (but still
	 *          accessible to screen-readers). (default: false)
	 */
	public function initializeLabelElement( array $config = [] ) {
		// Properties
		// FIXME 'labelElement' is a very stupid way to call '$label'
		$this->label = $config['labelElement'] ?? new Tag( 'span' );

		// Initialization
		$this->label->addClasses( [ 'oo-ui-labelElement-label' ] );
		$this->setLabel( $config['label'] ?? null );
		$this->setInvisibleLabel( $config['invisibleLabel'] ?? false );

		$this->registerConfigCallback( function ( &$config ) {
			if ( $this->labelValue !== null ) {
				$config['label'] = $this->labelValue;
			}
			if ( $this->invisibleLabel !== false ) {
				$config['invisibleLabel'] = $this->invisibleLabel;
			}
		} );
	}

	/**
	 * Set the label.
	 *
	 * An empty string will result in the label being hidden. A string containing only whitespace will
	 * be converted to a single `&nbsp;`.
	 *
	 * @param string|HtmlSnippet|null $label Label text
	 * @return $this
	 */
	public function setLabel( $label ) {
		$this->labelValue = (string)$label !== '' ? $label : null;

		$this->label->clearContent();
		if ( $this->labelValue !== null ) {
			if ( is_string( $this->labelValue ) && trim( $this->labelValue ) === '' ) {
				$this->label->appendContent( new HtmlSnippet( '&nbsp;' ) );
			} else {
				$this->label->appendContent( $label );
			}
		}

		$visibleLabel = $this->labelValue !== null && !$this->invisibleLabel;
		$this->toggleClasses( [ 'oo-ui-labelElement' ], $visibleLabel );

		return $this;
	}

	/**
	 * Set whether the label should be visually hidden (but still accessible to screen-readers).
	 *
	 * An empty string will result in the label being hidden. A string containing only whitespace will
	 * be converted to a single `&nbsp;`.
	 *
	 * @param bool $invisibleLabel
	 * @return $this
	 */
	public function setInvisibleLabel( $invisibleLabel ) {
		$this->invisibleLabel = (bool)$invisibleLabel;

		$this->label->toggleClasses( [ 'oo-ui-labelElement-invisible' ], $this->invisibleLabel );
		// Pretend that there is no label, a lot of CSS has been written with this assumption
		$visibleLabel = $this->labelValue !== null && !$this->invisibleLabel;
		$this->toggleClasses( [ 'oo-ui-labelElement' ], $visibleLabel );

		return $this;
	}

	/**
	 * Get the label.
	 *
	 * @return string|HtmlSnippet|null Label text
	 */
	public function getLabel() {
		return $this->labelValue;
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

<?php

namespace OOUI;

/**
 * A button that is an input widget. Intended to be used within a FormLayout.
 */
class ButtonInputWidget extends InputWidget {
	use ButtonElement;
	use IconElement;
	use IndicatorElement;
	use LabelElement {
		LabelElement::setLabel as setLabelElementLabel;
	}
	use FlaggedElement;

	/* Static Properties */

	/** @var string */
	public static $tagName = 'span';

	/* Properties */

	/**
	 * Whether to use `<input>` rather than `<button>`.
	 *
	 * @var bool
	 */
	protected $useInputTag;

	/**
	 * Whether to use `formnovalidate` attribute.
	 *
	 * @var bool
	 */
	protected $formNoValidate;

	/**
	 * @param array $config Configuration options
	 *      - string $config['type'] HTML tag `type` attribute, may be 'button', 'submit' or 'reset'
	 *          (default: 'button')
	 *      - bool $config['useInputTag'] Whether to use `<input>` rather than `<button>`. Only
	 *          useful if you need IE 6 support in a form with multiple buttons. If you use this
	 *          option, icons and indicators will not be displayed, it won't be possible to have a
	 *          non-plaintext label, and it won't be possible to set a value (which will internally
	 *          become identical to the label). (default: false)
	 *      - bool $config['formNoValidate'] Whether to use `formnovalidate` attribute.
	 */
	public function __construct( array $config = [] ) {
		// Configuration initialization
		$config = array_merge( [ 'type' => 'button', 'useInputTag' => false, 'formNoValidate' => false ], $config );

		// Properties (must be set before parent constructor, which calls setValue())
		$this->useInputTag = $config['useInputTag'];

		// Parent constructor
		parent::__construct( $config );

		// Traits
		$this->initializeButtonElement(
			array_merge( [ 'button' => $this->input ], $config )
		);
		$this->initializeIconElement( $config );
		$this->initializeIndicatorElement( $config );
		$this->initializeLabelElement( $config );
		$this->initializeFlaggedElement(
			array_merge( [ 'flagged' => $this ], $config )
		);

		// Initialization
		if ( !$config['useInputTag'] ) {
			$this->input->appendContent( $this->icon, $this->label, $this->indicator );
		}

		if ( $config['formNoValidate'] ) {
			$this->input->setAttributes( [
				'formnovalidate' => 'formnovalidate'
			] );
		}

		$this->addClasses( [ 'oo-ui-buttonInputWidget' ] );
	}

	/** @inheritDoc */
	protected function getInputElement( $config ) {
		$type = in_array( $config['type'], [ 'button', 'submit', 'reset' ] ) ?
			$config['type'] :
			'button';
		$tag = $config['useInputTag'] ? 'input' : 'button';
		return ( new Tag( $tag ) )->setAttributes( [ 'type' => $type ] );
	}

	/**
	 * Set label value.
	 *
	 * Overridden to support setting the 'value' of `<input>` elements.
	 *
	 * @param string|null $label Label text
	 * @return $this
	 */
	public function setLabel( $label ) {
		if ( $this->useInputTag ) {
			// Discard non-plaintext labels
			if ( !is_string( $label ) ) {
				$label = '';
			}

			$this->input->setValue( $label );
		}

		return $this->setLabelElementLabel( $label );
	}

	/**
	 * Set the value of the input.
	 *
	 * Overridden to disable for `<input>` elements, which have value identical to the label.
	 *
	 * @param mixed $value New value should be a string
	 * @return $this
	 */
	public function setValue( $value ) {
		if ( !$this->useInputTag ) {
			parent::setValue( $value );
		}
		return $this;
	}

	/**
	 * @return null
	 */
	public function getInputId() {
		// Disable generating `<label>` elements for buttons. One would very rarely need additional label
		// for a button, and it's already a big clickable target, and it causes unexpected rendering.
		return null;
	}

	/** @inheritDoc */
	public function getConfig( &$config ) {
		if ( $this->useInputTag ) {
			$config['useInputTag'] = true;
		}
		$config['type'] = $this->input->getAttribute( 'type' );
		if ( $this->input->getAttribute( 'formnovalidate' ) ) {
			$config['formNoValidate'] = true;
		}
		return parent::getConfig( $config );
	}
}

<?php

namespace OOUI;

/**
 * Input widget with a text field.
 */
class TextInputWidget extends InputWidget {
	use IconElement;
	use IndicatorElement;
	use FlaggedElement;
	use RequiredElement;

	/* Properties */

	/**
	 * Input field type.
	 *
	 * @var string
	 */
	protected $type = null;

	/**
	 * Prevent changes.
	 *
	 * @var bool
	 */
	protected $readOnly = false;

	/**
	 * @param array $config Configuration options
	 *      - string $config['type'] HTML tag `type` attribute: 'text', 'password', 'email',
	 *          'url' or 'number'. (default: 'text')
	 *      - string $config['placeholder'] Placeholder text
	 *      - bool $config['autofocus'] Ask the browser to focus this widget, using the 'autofocus'
	 *          HTML attribute (default: false)
	 *      - bool $config['readOnly'] Prevent changes (default: false)
	 *      - int $config['maxLength'] Maximum allowed number of characters to input
	 *          For unfortunate historical reasons, this counts the number of UTF-16 code units rather
	 *          than Unicode codepoints, which means that codepoints outside the Basic Multilingual
	 *          Plane (e.g. many emojis) count as 2 characters each.
	 *      - int $config['minLength'] Minimum allowed number of characters to input
	 *          Same considerations for maxLength apply as mentioned above.
	 *      - bool|string $config['autocomplete'] If the field should support autocomplete
	 *          or not (default: true). Can also be an autocomplete type hint.
	 *      - bool $config['spellcheck'] If the field should support spellcheck
	 *          or not (default: browser-dependent)
	 */
	public function __construct( array $config = [] ) {
		// Config initialization
		if ( is_bool( $config['autocomplete'] ?? null ) ) {
			$config['autocomplete'] = $config['autocomplete'] ? 'on' : 'off';
		}

		// Parent constructor
		parent::__construct( $config );

		// Properties
		$this->type = $this->getValidType( $config );

		// Traits
		$this->initializeIconElement( $config );
		$this->initializeIndicatorElement( $config );
		$this->initializeFlaggedElement(
			array_merge( [ 'flagged' => $this ], $config )
		);
		$this->initializeRequiredElement( $config );

		// Initialization
		$this
			->addClasses( [
				'oo-ui-textInputWidget',
				'oo-ui-textInputWidget-type-' . $this->type,
				'oo-ui-textInputWidget-php',
			] )
			->appendContent( $this->icon, $this->indicator );
		$this->setReadOnly( $config['readOnly'] ?? false );
		if ( isset( $config['placeholder'] ) ) {
			$this->input->setAttributes( [ 'placeholder' => $config['placeholder'] ] );
		}
		if ( isset( $config['maxLength'] ) ) {
			$this->input->setAttributes( [ 'maxlength' => $config['maxLength'] ] );
		}
		if ( isset( $config['minLength'] ) ) {
			$this->input->setAttributes( [ 'minlength' => $config['minLength'] ] );
		}
		if ( $config['autofocus'] ?? false ) {
			$this->input->setAttributes( [ 'autofocus' => 'autofocus' ] );
		}
		if ( isset( $config['autocomplete'] ) ) {
			$this->input->setAttributes( [ 'autocomplete' => $config['autocomplete'] ] );
		}
		if ( isset( $config['spellcheck'] ) ) {
			$this->input->setAttributes( [ 'spellcheck' => $config['spellcheck'] ? 'true' : 'false' ] );
		}
	}

	/**
	 * Check if the widget is read-only.
	 *
	 * @return bool
	 */
	public function isReadOnly() {
		return $this->readOnly;
	}

	/**
	 * Set the read-only state of the widget. This should probably change the widget's appearance and
	 * prevent it from being used.
	 *
	 * @param bool $state Make input read-only
	 * @return $this
	 */
	public function setReadOnly( $state ) {
		$this->readOnly = (bool)$state;
		if ( $this->readOnly ) {
			$this->input->setAttributes( [ 'readonly' => 'readonly' ] );
		} else {
			$this->input->removeAttributes( [ 'readonly' ] );
		}
		return $this;
	}

	/** @inheritDoc */
	protected function getInputElement( $config ) {
		if ( $this->getValidType( $config ) === 'number' ) {
			return ( new Tag( 'input' ) )->setAttributes( [
				'step' => 'any',
				'type' => 'number',
			] );
		} else {
			return ( new Tag( 'input' ) )->setAttributes( [ 'type' => $this->getValidType( $config ) ] );
		}
	}

	/**
	 * @param array $config
	 * @return string
	 */
	protected function getValidType( $config ) {
		$allowedTypes = [
			'password',
			'email',
			'url',
			'number'
		];
		return isset( $config['type'] ) && in_array( $config['type'], $allowedTypes ) ? $config['type'] : 'text';
	}

	/** @inheritDoc */
	public function getConfig( &$config ) {
		if ( $this->type !== 'text' ) {
			$config['type'] = $this->type;
		}
		if ( $this->isReadOnly() ) {
			$config['readOnly'] = true;
		}
		$placeholder = $this->input->getAttribute( 'placeholder' );
		if ( $placeholder !== null ) {
			$config['placeholder'] = $placeholder;
		}
		$maxlength = $this->input->getAttribute( 'maxlength' );
		if ( $maxlength !== null ) {
			$config['maxLength'] = $maxlength;
		}
		$minLength = $this->input->getAttribute( 'minlength' );
		if ( $minLength !== null ) {
			$config['minLength'] = $minLength;
		}
		$autofocus = $this->input->getAttribute( 'autofocus' );
		if ( $autofocus !== null ) {
			$config['autofocus'] = true;
		}
		$autocomplete = $this->input->getAttribute( 'autocomplete' );
		if ( $autocomplete !== null ) {
			$config['autocomplete'] = $autocomplete;
		}
		return parent::getConfig( $config );
	}
}

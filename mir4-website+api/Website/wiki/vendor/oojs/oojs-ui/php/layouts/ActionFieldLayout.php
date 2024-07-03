<?php

namespace OOUI;

/**
 * Layout made of a field, button and optional label.
 */
class ActionFieldLayout extends FieldLayout {

	/**
	 * Button widget to be laid out.
	 *
	 * @var Widget
	 */
	protected $buttonWidget;

	/**
	 * @var Tag
	 */
	protected $button;

	/**
	 * @var Tag
	 */
	protected $input;

	/**
	 * @param Widget $fieldWidget Field widget
	 * @param ButtonWidget|ButtonInputWidget $buttonWidget Field widget
	 * @param array $config Configuration options
	 * @suppress PhanTypeMismatchDefault Overloaded method
	 */
	public function __construct( $fieldWidget, $buttonWidget = false, array $config = [] ) {
		// Allow passing positional parameters inside the config array
		if ( is_array( $fieldWidget ) && isset( $fieldWidget['fieldWidget'] ) ) {
			$config = $fieldWidget;
			[ 'fieldWidget' => $fieldWidget, 'buttonWidget' => $buttonWidget ] = $config;
		}

		// Parent constructor
		parent::__construct( $fieldWidget, $config );

		// Properties
		$this->buttonWidget = $buttonWidget;
		$this->button = new Tag( 'span' );
		$this->input = $this->isFieldInline() ? new Tag( 'span' ) : new Tag( 'div' );

		// Initialization
		$this->addClasses( [ 'oo-ui-actionFieldLayout' ] );
		$this->button
			->addClasses( [ 'oo-ui-actionFieldLayout-button' ] )
			->appendContent( $this->buttonWidget );
		$this->input
			->addClasses( [ 'oo-ui-actionFieldLayout-input' ] )
			->appendContent( $this->fieldWidget );
		$this->field
			->clearContent()
			->appendContent( $this->input, $this->button );
	}

	/** @inheritDoc */
	public function getConfig( &$config ) {
		$config['buttonWidget'] = $this->buttonWidget;
		return parent::getConfig( $config );
	}
}

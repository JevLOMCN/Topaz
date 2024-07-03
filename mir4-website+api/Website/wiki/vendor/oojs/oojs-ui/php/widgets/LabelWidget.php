<?php

namespace OOUI;

/**
 * Label widget.
 */
class LabelWidget extends Widget {
	use LabelElement;

	/* Static Properties */

	/** @var string */
	public static $tagName = 'label';

	/* Properties */

	/**
	 * Associated input element.
	 *
	 * @var InputWidget|null
	 */
	protected $input;

	/**
	 * @param array $config Configuration options
	 *      - InputWidget $config['input'] Input widget this label is for
	 */
	public function __construct( array $config = [] ) {
		// Parent constructor
		parent::__construct( $config );

		// Traits
		$this->initializeLabelElement(
			array_merge( [ 'labelElement' => $this ], $config )
		);

		// Properties
		$this->input = $config['input'] ?? null;

		// Initialization
		if ( $this->input && $this->input->getInputId() ) {
			$this->setAttributes( [ 'for' => $this->input->getInputId() ] );
		}
		$this->addClasses( [ 'oo-ui-labelWidget' ] );
	}

	/** @inheritDoc */
	public function getConfig( &$config ) {
		if ( $this->input !== null ) {
			$config['input'] = $this->input;
		}
		return parent::getConfig( $config );
	}
}

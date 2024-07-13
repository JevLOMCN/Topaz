<?php

namespace OOUI;

/**
 * Generic widget for buttons.
 */
class SelectFileInputWidget extends InputWidget {
	use RequiredElement;

	/* Static Properties */

	/** @var string[]|null */
	protected $accept;
	/** @var bool */
	protected $multiple;
	/** @var string|null */
	protected $placeholder;
	/** @var array|null */
	protected $button;
	/** @var string|null */
	protected $icon;
	/** @var bool */
	protected $droppable;
	/** @var bool */
	protected $buttonOnly;
	/** @var bool */
	protected $showDropTarget;
	/** @var float */
	protected $thumbnailSizeLimit;

	/**
	 * @param array $config Configuration options
	 *      - string[]|null $config['accept'] MIME types to accept. null accepts all types.
	 *          (default: null)
	 *      - bool $config['multiple'] Allow multiple files to be selected.
	 *      - string $config['placeholder'] Text to display when no file is selected.
	 *      - array $config['button'] Config to pass to select file button.
	 *      - string $config['icon'] Icon to show next to file info
	 *          and show a preview (for performance).
	 *      - boolean $config['droppable'] Whether to accept files by drag and drop. (default: true)
	 *      - boolean $config['buttonOnly'] Show only the select file button, no info field. Requires
	 *          showDropTarget to be false.
	 *      - boolean $config['showDropTarget'] Whether to show a drop target. Requires droppable to be
	 *          true.
	 *      - number $config['thumbnailSizeLimit'] File size limit in MiB above which to not try and show a
	 *          preview (for performance). (default: 20)
	 */
	public function __construct( array $config = [] ) {
		// Parent constructor
		parent::__construct( $config );

		// Properties
		$this->accept = $config['accept'] ?? null;
		$this->multiple = $config['multiple'] ?? false;
		$this->placeholder = $config['placeholder'] ?? null;
		$this->button = $config['button'] ?? null;
		$this->icon = $config['icon'] ?? null;
		$this->droppable = $config['droppable'] ?? true;
		$this->buttonOnly = $config['buttonOnly'] ?? false;
		$this->showDropTarget = $config['showDropTarget'] ?? false;
		$this->thumbnailSizeLimit = $config['thumbnailSizeLimit'] ?? 20;

		// Traits
		$this->initializeRequiredElement(
			array_merge( [ 'indicatorElement' => null ], $config )
		);

		// Initialization
		$this->addClasses( [ 'oo-ui-selectFileInputWidget' ] );
		$this->input->setAttributes( [
			'type' => 'file'
		] );
		if ( $this->multiple ) {
			$this->input->setAttributes( [
				'multiple' => ''
			] );
		}
		if ( $this->accept ) {
			$this->input->setAttributes( [
				'accept' => implode( ',', $this->accept )
			] );
		}
	}

	/** @inheritDoc */
	public function getConfig( &$config ) {
		if ( $this->accept !== null ) {
			$config['accept'] = $this->accept;
		}
		if ( $this->multiple !== null ) {
			$config['multiple'] = $this->multiple;
		}
		if ( $this->placeholder !== null ) {
			$config['placeholder'] = $this->placeholder;
		}
		if ( $this->button !== null ) {
			$config['button'] = $this->button;
		}
		if ( $this->icon !== null ) {
			$config['icon'] = $this->icon;
		}
		if ( $this->droppable !== null ) {
			$config['droppable'] = $this->droppable;
		}
		if ( $this->buttonOnly !== null ) {
			$config['buttonOnly'] = $this->buttonOnly;
		}
		if ( $this->showDropTarget !== null ) {
			$config['showDropTarget'] = $this->showDropTarget;
		}
		if ( $this->thumbnailSizeLimit !== null ) {
			$config['thumbnailSizeLimit'] = $this->thumbnailSizeLimit;
		}
		return parent::getConfig( $config );
	}
}

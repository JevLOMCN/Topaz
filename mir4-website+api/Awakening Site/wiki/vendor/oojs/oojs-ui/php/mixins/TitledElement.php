<?php

namespace OOUI;

/**
 * Element with a title.
 *
 * Titles are rendered by the browser and are made visible when hovering the element. Titles are
 * not visible on touch devices.
 *
 * @abstract
 */
trait TitledElement {
	/**
	 * Title text.
	 *
	 * @var string
	 */
	protected $title = null;

	/**
	 * @var Element
	 */
	protected $titled;

	/**
	 * @param array $config Configuration options
	 *      - string $config['title'] Title. If not provided, the static property 'title' is used.
	 *        If config for an invisible label (LabelElement) is present, and a title is
	 *        omitted, the label will be used as a fallback for the title.
	 */
	public function initializeTitledElement( array $config = [] ) {
		// Properties
		$this->titled = $config['titled'] ?? $this;

		// Initialization
		$title = $config['title'] ?? null;
		if (
			$title === null &&
			isset( $config['invisibleLabel'] ) && $config['invisibleLabel'] &&
			isset( $config['label'] ) && is_string( $config['label'] )
		) {
			// If config for an invisible label is present, use this as a fallback title
			$title = $config['label'];
		}
		$this->setTitle( $title );

		$this->registerConfigCallback( function ( &$config ) {
			if ( $this->title !== null ) {
				$config['title'] = $this->title;
			}
		} );
	}

	/**
	 * Set title.
	 *
	 * @param string|null $title Title text or null for browser default title, which is no title for
	 *   most elements.
	 * @return $this
	 */
	public function setTitle( $title ) {
		if ( $this->title !== $title ) {
			$this->title = $title;
			$this->updateTitle();
		}

		return $this;
	}

	/**
	 * Update the title attribute, in case of changes to title or accessKey.
	 *
	 * @return $this
	 */
	protected function updateTitle() {
		$title = $this->getTitle();
		if ( $title !== null ) {
			// Only if this is an AccessKeyedElement
			if ( method_exists( $this, 'formatTitleWithAccessKey' ) ) {
				// @phan-suppress-next-line PhanUndeclaredMethod
				$title = $this->formatTitleWithAccessKey( $title );
			}
			$this->titled->setAttributes( [ 'title' => $title ] );
		} else {
			$this->titled->removeAttributes( [ 'title' ] );
		}
		return $this;
	}

	/**
	 * Get title.
	 *
	 * @return string Title string
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * @param callable $func
	 */
	abstract public function registerConfigCallback( callable $func );
}

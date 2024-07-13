<?php

namespace OOUI;

/**
 * HorizontalLayout arranges its contents in a single line (using `display: inline-block` for its
 * items), with small margins between them.
 */
class HorizontalLayout extends Layout {
	use GroupElement;

	/**
	 * @param array $config Configuration options
	 *      - Widget[]|Layout[] $config['items'] Widgets or other layouts to add to the layout.
	 */
	public function __construct( array $config = [] ) {
		// Parent constructor
		parent::__construct( $config );

		// Traits
		$this->initializeGroupElement( array_merge( [ 'group' => $this ], $config ) );

		// Initialization
		$this->addClasses( [ 'oo-ui-horizontalLayout' ] );
		if ( isset( $config['items'] ) ) {
			$this->addItems( $config['items'] );
		}
	}
}

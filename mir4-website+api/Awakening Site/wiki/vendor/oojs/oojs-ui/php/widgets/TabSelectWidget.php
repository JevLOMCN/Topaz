<?php

namespace OOUI;

/**
 * TabSelectWidget is a list that contains TabOptionWidget options
 */
class TabSelectWidget extends SelectWidget {
	use TabIndexedElement;

	/** @var bool */
	protected $framed;

	/**
	 * @param array $config Configuration options
	 *      - bool $config['framed'] Use framed tabs (default: true)
	 */
	public function __construct( array $config = [] ) {
		parent::__construct( $config );

		$this->initializeTabIndexedElement( array_merge( $config, [ 'tabIndexed' => $this ] ) );

		$this->addClasses( [ 'oo-ui-tabSelectWidget' ] );
		$this->toggleFramed( $config[ 'framed' ] ?? true );
		$this->setAttributes( [
			'role' => 'tablist'
		] );
	}

	/**
	 * Check if tabs are framed.
	 *
	 * @return bool Tabs are framed
	 */
	public function isFramed() {
		return $this->framed;
	}

	/**
	 * Render the tabs with or without frames.
	 *
	 * @param bool|null $framed Make tabs framed, omit to toggle
	 * @return $this
	 */
	public function toggleFramed( $framed = null ) {
		$this->framed = $framed !== null ? (bool)$framed : !$this->framed;
		$this->toggleClasses( [ 'oo-ui-tabSelectWidget-framed' ], $this->framed );
		$this->toggleClasses( [ 'oo-ui-tabSelectWidget-frameless' ], !$this->framed );
		return $this;
	}

	/** @inheritDoc */
	public function getConfig( &$config ) {
		if ( $this->framed !== true ) {
			$config['framed'] = $this->framed;
		}
		return parent::getConfig( $config );
	}
}

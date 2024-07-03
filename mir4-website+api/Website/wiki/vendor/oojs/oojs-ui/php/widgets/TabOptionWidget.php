<?php

namespace OOUI;

class TabOptionWidget extends OptionWidget {

	/** @var string|null */
	protected $href;

	/**
	 * @param array $config Configuration options
	 *      - string $config['href'] Hyperlink to add to TabOption. Mostly used in OOUI PHP.
	 */
	public function __construct( array $config = [] ) {
		$this->href = $config['href'] ?? false;
		if ( $this->href ) {
			$link = new Tag( 'a' );
			$link->setAttributes( [ 'href' => $config['href'] ] );
			$config = array_merge( [
				'labelElement' => $link
			], $config );
		}

		// Parent constructor
		parent::__construct( $config );

		// Initialisation
		$this->addClasses( [ 'oo-ui-tabOptionWidget' ] );
		$this->setAttributes( [
			'role' => 'tab'
		] );
	}

	/** @inheritDoc */
	public function getConfig( &$config ) {
		if ( $this->href ) {
			$config['href'] = $this->href;
		}
		return parent::getConfig( $config );
	}
}

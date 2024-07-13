<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Ext\Gallery;

class NoLinesMode extends TraditionalMode {
	/**
	 * Create a NoLinesMode singleton.
	 * @param ?string $mode Only used by subclasses.
	 */
	protected function __construct( ?string $mode = null ) {
		parent::__construct( $mode ?? 'nolines' );
		$this->padding = (object)[ 'thumb' => 0, 'box' => 5, 'border' => 4 ];
	}
}

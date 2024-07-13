<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Ext\Gallery;

use Wikimedia\Parsoid\DOM\Element;

class SlideshowMode extends TraditionalMode {
	/**
	 * Create a SlideshowMode singleton.
	 * @param ?string $mode Only used by subclasses.
	 */
	protected function __construct( ?string $mode = null ) {
		parent::__construct( $mode ?? 'slideshow' );
	}

	/** @inheritDoc */
	protected function setAdditionalOptions( Opts $opts, Element $ul ): void {
		$ul->setAttribute( 'data-showthumbnails', $opts->showthumbnails ? '1' : '' );
	}

	/** @inheritDoc */
	protected function perRow( Opts $opts, Element $ul ): void {
		/* do nothing */
	}

	public function getModules(): array {
		return [ 'mediawiki.page.gallery.slideshow' ];
	}

}

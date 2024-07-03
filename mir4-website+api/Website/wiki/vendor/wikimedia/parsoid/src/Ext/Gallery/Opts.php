<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Ext\Gallery;

use Wikimedia\Parsoid\Core\Sanitizer;
use Wikimedia\Parsoid\Ext\ParsoidExtensionAPI;
use Wikimedia\Parsoid\Ext\Utils;

class Opts {
	/**
	 * Parse options from an attribute array.
	 * @param ParsoidExtensionAPI $extApi
	 * @param array<string,string> $attrs The attribute array
	 */
	public function __construct( ParsoidExtensionAPI $extApi, array $attrs ) {
		// Set default values from config
		// The options 'showDimensions' and 'showBytes' for traditional mode are not implemented,
		// They are not used for galleries in wikitext (only on category pages or special pages)
		// The deprecated option 'captionLength' for traditional mode is not implemented.
		$galleryOptions = $extApi->getSiteConfig()->galleryOptions();
		$this->imagesPerRow = $galleryOptions['imagesPerRow'];
		$this->imageWidth = $galleryOptions['imageWidth'];
		$this->imageHeight = $galleryOptions['imageHeight'];
		$this->mode = $galleryOptions['mode'];

		// Override values from given attributes
		if ( is_numeric( $attrs['perrow'] ?? null ) ) {
			$this->imagesPerRow = intval( $attrs['perrow'], 10 );
		}

		$maybeDim = Utils::parseMediaDimensions( $attrs['widths'] ?? '', true );
		if ( $maybeDim && Utils::validateMediaParam( $maybeDim['x'] ) ) {
			$this->imageWidth = $maybeDim['x'];
		}

		$maybeDim = Utils::parseMediaDimensions( $attrs['heights'] ?? '', true );
		if ( $maybeDim && Utils::validateMediaParam( $maybeDim['x'] ) ) {
			$this->imageHeight = $maybeDim['x'];
		}

		$mode = strtolower( $attrs['mode'] ?? '' );
		if ( Mode::byName( $mode ) !== null ) {
			$this->mode = $mode;
		}

		$this->showfilename = isset( $attrs['showfilename'] );
		$this->showthumbnails = isset( $attrs['showthumbnails'] );
		$this->caption = (bool)( $attrs['caption'] ?? false );

		// TODO: Good contender for T54941
		$validUlAttrs = Sanitizer::attributesAllowedInternal( 'ul' );
		$this->attrs = [];
		foreach ( $attrs as $k => $v ) {
			if ( !isset( $validUlAttrs[$k] ) ) {
				continue;
			}
			if ( $k === 'style' ) {
				$v = Sanitizer::checkCss( $v );
			}
			$this->attrs[$k] = $v;
		}
	}

	/** @var array<string,string> */
	public $attrs;

	/** @var int */
	public $imagesPerRow;

	/** @var int */
	public $imageWidth;

	/** @var int */
	public $imageHeight;

	/** @var string */
	public $mode;

	/** @var bool */
	public $showfilename;

	/** @var bool */
	public $showthumbnails;

	/** @var bool */
	public $caption;

}

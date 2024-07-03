<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Ext\Gallery;

use Wikimedia\Parsoid\DOM\Document;
use Wikimedia\Parsoid\DOM\DocumentFragment;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\Ext\DOMDataUtils;
use Wikimedia\Parsoid\Ext\DOMUtils;
use Wikimedia\Parsoid\Ext\ParsoidExtensionAPI;
use Wikimedia\Parsoid\Utils\DOMCompat;

class TraditionalMode extends Mode {
	/**
	 * Create a TraditionalMode singleton.
	 * @param ?string $mode Only used by subclasses.
	 */
	protected function __construct( ?string $mode = null ) {
		parent::__construct( $mode ?? 'traditional' );
		$this->scale = 1;
		$this->padding = (object)[ 'thumb' => 30, 'box' => 5, 'border' => 8 ];
	}

	/** @var float */
	protected $scale;
	/** @var \stdClass */
	protected $padding;

	private function appendAttr( Element $ul, string $k, string $v ): void {
		$val = DOMCompat::getAttribute( $ul, $k );
		$val = ( $val === null || trim( $val ) === '' ) ? $v : "$val $v";
		$ul->setAttribute( $k, $val );
	}

	private function ul(
		Opts $opts, DocumentFragment $domFragment
	): Element {
		$ul = $domFragment->ownerDocument->createElement( 'ul' );
		$cl = 'gallery mw-gallery-' . $this->mode;
		$ul->setAttribute( 'class', $cl );
		foreach ( $opts->attrs as $k => $v ) {
			$this->appendAttr( $ul, $k, $v );
		}
		$domFragment->appendChild( $ul );
		$this->perRow( $opts, $ul );
		$this->setAdditionalOptions( $opts, $ul );
		return $ul;
	}

	protected function perRow( Opts $opts, Element $ul ): void {
		if ( $opts->imagesPerRow > 0 ) {
			$padding = $this->padding;
			$total = $opts->imageWidth + $padding->thumb + $padding->box + $padding->border;
			$total *= $opts->imagesPerRow;
			$this->appendAttr( $ul, 'style', 'max-width: ' . $total . 'px;' );
		}
	}

	protected function setAdditionalOptions( Opts $opts, Element $ul ): void {
	}

	private function caption(
		Opts $opts, Element $ul, DocumentFragment $caption
	): void {
		$doc = $ul->ownerDocument;
		$li = $doc->createElement( 'li' );
		$li->setAttribute( 'class', 'gallerycaption' );
		DOMUtils::migrateChildren( $caption, $li );
		$ul->appendChild( $doc->createTextNode( "\n" ) );
		$ul->appendChild( $li );
	}

	/** @inheritDoc */
	public function dimensions( Opts $opts ): string {
		return "{$opts->imageWidth}x{$opts->imageHeight}px";
	}

	/**
	 * @param Opts $opts
	 * @param Element $wrapper
	 * @return int|float
	 */
	protected function scaleMedia( Opts $opts, Element $wrapper ) {
		return $opts->imageWidth;
	}

	/**
	 * @param float|int $width
	 * @return float|int
	 */
	protected function thumbWidth( $width ) {
		return $width + $this->padding->thumb;
	}

	/**
	 * @param float|int $height
	 * @return float|int
	 */
	protected function thumbHeight( $height ) {
		return $height + $this->padding->thumb;
	}

	/**
	 * @param float|int $width
	 * @param float|int $height
	 * @return string
	 */
	protected function thumbStyle( $width, $height ): string {
		$style = [ 'width: ' . $this->thumbWidth( $width ) . 'px;' ];
		if ( $this->mode === 'traditional' ) {
			$style[] = 'height: ' . $this->thumbHeight( $height ) . 'px;';
		}
		return implode( ' ', $style );
	}

	/**
	 * @param float|int $width
	 * @return float|int
	 */
	protected function boxWidth( $width ) {
		return $this->thumbWidth( $width ) + $this->padding->box;
	}

	/**
	 * @param float|int $width
	 * @param float|int $height
	 * @return string
	 */
	protected function boxStyle( $width, $height ): string {
		return 'width: ' . $this->boxWidth( $width ) . 'px;';
	}

	protected function galleryText(
		Document $doc, Element $box, ?Element $gallerytext,
		float $width
	): void {
		$div = $doc->createElement( 'div' );
		$div->setAttribute( 'class', 'gallerytext' );
		if ( $gallerytext ) {
			ParsoidExtensionAPI::migrateChildrenAndTransferWrapperDataAttribs(
				$gallerytext, $div
			);
		}
		$box->appendChild( $div );
	}

	private function line( Opts $opts, Element $ul, ParsedLine $o ): void {
		$doc = $ul->ownerDocument;

		$width = $this->scaleMedia( $opts, $o->thumb );
		$height = $opts->imageHeight;

		$box = $doc->createElement( 'li' );
		$box->setAttribute( 'class', 'gallerybox' );
		$box->setAttribute( 'style', $this->boxStyle( $width, $height ) );
		DOMDataUtils::getDataParsoid( $box )->dsr = $o->dsr;

		$thumb = $doc->createElement( 'div' );
		$thumb->setAttribute( 'class', 'thumb' );
		$thumb->setAttribute( 'style', $this->thumbStyle( $width, $height ) );

		$wrapper = $doc->createElement( 'span' );
		$wrapper->setAttribute( 'typeof', $o->rdfaType );
		ParsoidExtensionAPI::migrateChildrenAndTransferWrapperDataAttribs(
			$o->thumb, $wrapper
		);
		$thumb->appendChild( $wrapper );

		$box->appendChild( $thumb );
		$this->galleryText( $doc, $box, $o->gallerytext, $width );
		$ul->appendChild( $doc->createTextNode( "\n" ) );
		$ul->appendChild( $box );
	}

	/** @inheritDoc */
	public function render(
		ParsoidExtensionAPI $extApi, Opts $opts, ?DocumentFragment $caption,
		array $lines
	): DocumentFragment {
		$domFragment = $extApi->htmlToDom( '' );
		$ul = $this->ul( $opts, $domFragment );
		if ( $caption ) {
			$this->caption( $opts, $ul, $caption );
		}
		foreach ( $lines as $l ) {
			$this->line( $opts, $ul, $l );
		}
		$ul->appendChild( $domFragment->ownerDocument->createTextNode( "\n" ) );
		return $domFragment;
	}

	public function getModuleStyles(): array {
		return [ 'mediawiki.page.gallery.styles' ];
	}

}

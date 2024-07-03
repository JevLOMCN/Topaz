<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Ext\Gallery;

use Wikimedia\Parsoid\Core\DomSourceRange;
use Wikimedia\Parsoid\DOM\Element;

class ParsedLine {

	/**
	 * DOM node representing the thumbnail image.
	 * @var Element
	 */
	public $thumb;

	/**
	 * DOM node representing the caption (if any).
	 * @var ?Element
	 */
	public $gallerytext;

	/**
	 * The `typeof` the thumbnail image.
	 * @var string
	 */
	public $rdfaType;

	/**
	 * @var DomSourceRange
	 */
	public $dsr;

	/**
	 * Construct a new ParsedLine object.
	 * @param Element $thumb
	 * @param ?Element $gallerytext
	 * @param string $rdfaType
	 * @param DomSourceRange $dsr
	 */
	public function __construct(
		Element $thumb, ?Element $gallerytext, string $rdfaType, DomSourceRange $dsr
	) {
		$this->thumb = $thumb;
		$this->gallerytext = $gallerytext;
		$this->rdfaType = $rdfaType;
		$this->dsr = $dsr;
	}
}

<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Core;

use Wikimedia\Parsoid\DOM\Element;

/**
 * A simple pair of DOM elements
 */
class ElementRange {
	/** @var Element */
	public $startElem;

	/** @var Element */
	public $endElem;
}

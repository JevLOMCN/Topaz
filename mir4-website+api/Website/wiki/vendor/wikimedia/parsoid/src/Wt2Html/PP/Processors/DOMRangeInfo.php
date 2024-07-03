<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Wt2Html\PP\Processors;

use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;

class DOMRangeInfo {
	/** @var Element */
	public $startElem;

	/** @var Element */
	public $endElem;

	/** @var Node|null */
	public $start;

	/** @var Node|null */
	public $end;

	/** @var string */
	public $id;

	/** @var int */
	public $startOffset;

	/** @var bool */
	public $flipped = false;

	/** @var bool */
	public $extendedByOverlapMerge = false;
}

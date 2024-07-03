<?php

namespace Wikimedia\RemexHtml\TreeBuilder;

use Wikimedia\RemexHtml\PropGuard;

/**
 * A pseudo-element used as a marker or bookmark in the list of active formatting elements
 */
class Marker implements FormattingElement {
	use PropGuard;

	public $nextAFE;
	public $prevAFE;
	public $nextNoah;
	public $type;

	public function __construct( $type ) {
		$this->type = $type;
	}
}

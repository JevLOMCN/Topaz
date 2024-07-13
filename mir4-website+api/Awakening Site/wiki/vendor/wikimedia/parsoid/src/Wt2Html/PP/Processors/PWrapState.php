<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Wt2Html\PP\Processors;

use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMUtils;

class PWrapState {

	private const RANGE_TYPE_RE = '!^mw:(Transclusion(/|$)|Param(/|$)|Annotation/)!';

	/** @var ?Element */
	public $p = null;

	/** @var bool */
	private $hasOptionalNode = false;

	/**
	 * About ids of starts we've seen in this paragraph
	 *
	 * @var array
	 */
	private $seenStarts = [];

	/**
	 * Unwrap + reset
	 */
	public function reset() {
		$this->unwrapTrailingPWrapOptionalNodes();

		$this->p = null;
		$this->hasOptionalNode = false;
		$this->seenStarts = [];
	}

	/**
	 * Record that we've encountered an optional node to potentially unwrap
	 *
	 * @param Node $n
	 */
	public function processOptionalNode( Node $n ) {
		$t = DOMUtils::matchNameAndTypeOf( $n, 'meta', self::RANGE_TYPE_RE );
		$this->hasOptionalNode = (bool)$t || $this->hasOptionalNode;
		if ( $t && !str_ends_with( $t, '/End' ) ) {
			'@phan-var Element $n';  // @var Element $n
			$this->seenStarts[DOMCompat::getAttribute( $n, 'about' )] = true;
		}
	}

	/**
	 * Unwrap a run of trailing nodes that don't need p-wrapping.
	 * This only matters for meta tags representing transclusions
	 * and annotations. Unwrapping can prevent unnecessary expansion
	 * of template/annotation ranges.
	 */
	private function unwrapTrailingPWrapOptionalNodes() {
		if ( $this->hasOptionalNode ) {
			$lastChild = $this->p->lastChild;
			while ( PWrap::pWrapOptional( $lastChild ) ) {
				$t = DOMUtils::matchNameAndTypeOf( $lastChild, 'meta', self::RANGE_TYPE_RE );
				if ( $t && str_ends_with( $t, '/End' ) ) {
					'@phan-var Element $lastChild';  // @var Element $lastChild
					// Check if one of its prior siblings has a matching opening tag.
					// If so, we are done with unwrapping here since we don't want to
					// hoist this closing tag by itself.
					$aboutId = DOMCompat::getAttribute( $lastChild, 'about' );
					if ( $this->seenStarts[$aboutId] ?? null ) {
						break;
					}
				}
				$this->p->parentNode->insertBefore( $lastChild, $this->p->nextSibling );
				$lastChild = $this->p->lastChild;
			}
		}
	}

}

<?php

namespace Wikimedia\RemexHtml\TreeBuilder;

use LogicException;

/**
 * The list of active formatting elements
 */
class ActiveFormattingElements {
	/**
	 * The last (most recent) element in the list
	 *
	 * @var Marker|null
	 */
	private $tail;

	/**
	 * The first (least recent) element in the list
	 *
	 * @var Marker|null
	 */
	private $head;

	/**
	 * An array of arrays representing the population of elements in each bucket
	 * according to the Noah's Ark clause. The outer array is stack-like, with each
	 * integer-indexed element representing a segment of the list, bounded by
	 * markers. The first element represents the segment of the list before the
	 * first marker.
	 *
	 * The inner arrays are indexed by "Noah key", which is a string which uniquely
	 * identifies each bucket according to the rules in the spec. The value in
	 * the inner array is the first (least recently inserted) element in the bucket,
	 * and subsequent members of the bucket can be found by iterating through the
	 * singly-linked list via $node->nextNoah.
	 *
	 * This is optimised for the most common case of inserting into a bucket
	 * with zero members, and deleting a bucket containing one member. In the
	 * worst case, iteration through the list is still O(1) in the document
	 * size, since each bucket can have at most 3 members.
	 *
	 * @var array<int,array<string,Element|Marker>>
	 */
	private $noahTableStack = [ [] ];

	/**
	 * Manually unlink the doubly-linked list, since otherwise, it is not freed
	 * due to reference cycles.
	 */
	public function __destruct() {
		for ( $node = $this->head; $node; $node = $next ) {
			$next = $node->nextAFE;
			$node->prevAFE = $node->nextAFE = $node->nextNoah = null;
		}
		// @phan-suppress-next-line PhanTypeMismatchPropertyProbablyReal
		$this->head = $this->tail = $this->noahTableStack = null;
	}

	/**
	 * Insert a marker
	 */
	public function insertMarker() {
		$elt = new Marker( 'marker' );
		if ( $this->tail ) {
			$this->tail->nextAFE = $elt;
			$elt->prevAFE = $this->tail;
		} else {
			$this->head = $elt;
		}
		$this->tail = $elt;
		$this->noahTableStack[] = [];
	}

	/**
	 * Follow the steps required when the spec requires us to "push onto the
	 * list of active formatting elements".
	 * @param Element $elt
	 */
	public function push( Element $elt ) {
		// Must not be in the list already
		if ( $elt->prevAFE !== null || $this->head === $elt ) {
			throw new LogicException( 'Cannot insert a node into the AFE list twice' );
		}

		// "Noah's Ark clause" -- if there are already three copies of
		// this element before we encounter a marker, then drop the last
		// one.
		$noahKey = $elt->getNoahKey();
		$table =& $this->noahTableStack[ count( $this->noahTableStack ) - 1 ];
		if ( !isset( $table[$noahKey] ) ) {
			$table[$noahKey] = $elt;
		} else {
			$count = 1;
			$head = $tail = $table[$noahKey];
			while ( $tail->nextNoah ) {
				$tail = $tail->nextNoah;
				$count++;
			}
			if ( $count >= 3 ) {
				$this->remove( $head );
			}
			$tail->nextNoah = $elt;
		}
		// Add to the main AFE list
		if ( $this->tail ) {
			$this->tail->nextAFE = $elt;
			$elt->prevAFE = $this->tail;
		} else {
			$this->head = $elt;
		}
		$this->tail = $elt;
	}

	/**
	 * Follow the steps required when the spec asks us to "clear the list of
	 * active formatting elements up to the last marker".
	 */
	public function clearToMarker() {
		// Iterate back through the list starting from the tail
		$tail = $this->tail;
		while ( $tail && !( $tail instanceof Marker ) ) {
			// Unlink the element
			$prev = $tail->prevAFE;
			$tail->prevAFE = null;
			if ( $prev ) {
				$prev->nextAFE = null;
			}
			$tail->nextNoah = null;
			$tail = $prev;
		}
		// If we finished on a marker, unlink it and pop it off the Noah table stack
		if ( $tail ) {
			$prev = $tail->prevAFE;
			if ( $prev ) {
				$prev->nextAFE = null;
			}
			$tail = $prev;
			array_pop( $this->noahTableStack );
		} else {
			// No marker: wipe the top-level Noah table (which is the only one)
			$this->noahTableStack[0] = [];
		}
		// If we removed all the elements, clear the head pointer
		if ( !$tail ) {
			$this->head = null;
		}
		$this->tail = $tail;
	}

	/**
	 * Find and return the last element with the specified name between the
	 * end of the list and the last marker on the list.
	 * Used when parsing <a> "in body mode".
	 * @param string $name
	 * @return Element|null
	 */
	public function findElementByName( $name ) {
		$elt = $this->tail;
		while ( $elt && !( $elt instanceof Marker ) ) {
			if ( $elt->htmlName === $name ) {
				return $elt;
			}
			$elt = $elt->prevAFE;
		}
		return null;
	}

	/**
	 * Determine whether an element is in the list of formatting elements.
	 * @param Element $elt
	 * @return bool
	 */
	public function isInList( Element $elt ) {
		return $this->head === $elt || $elt->prevAFE;
	}

	/**
	 * Find the element $elt in the list and remove it.
	 * Used when parsing <a> in body mode.
	 *
	 * @param FormattingElement $elt
	 */
	public function remove( FormattingElement $elt ) {
		'@phan-var Marker|Element $elt'; /** @var Marker|Element $elt */
		if ( $this->head !== $elt && !$elt->prevAFE ) {
			throw new TreeBuilderError(
				"Attempted to remove an element which is not in the AFE list" );
		}
		// Update head and tail pointers
		if ( $this->head === $elt ) {
			$this->head = $elt->nextAFE;
		}
		if ( $this->tail === $elt ) {
			$this->tail = $elt->prevAFE;
		}
		// Update previous element
		if ( $elt->prevAFE ) {
			$elt->prevAFE->nextAFE = $elt->nextAFE;
		}
		// Update next element
		if ( $elt->nextAFE ) {
			$elt->nextAFE->prevAFE = $elt->prevAFE;
		}
		// Clear pointers so that isInList() etc. will work
		$elt->prevAFE = $elt->nextAFE = null;
		// Update Noah list
		if ( $elt instanceof Element ) {
			$this->removeFromNoahList( $elt );
		}
	}

	/**
	 * Add an element to a bucket of elements which are alike for the purposes
	 * of the Noah's Ark clause.
	 *
	 * @param Element $elt
	 */
	private function addToNoahList( Element $elt ) {
		$noahKey = $elt->getNoahKey();
		$table =& $this->noahTableStack[ count( $this->noahTableStack ) - 1 ];
		if ( !isset( $table[$noahKey] ) ) {
			$table[$noahKey] = $elt;
		} else {
			$tail = $table[$noahKey];
			while ( $tail->nextNoah ) {
				$tail = $tail->nextNoah;
			}
			$tail->nextNoah = $elt;
		}
	}

	/**
	 * Remove an element from its Noah's Ark bucket.
	 *
	 * @param Element $elt
	 */
	private function removeFromNoahList( Element $elt ) {
		$table =& $this->noahTableStack[ count( $this->noahTableStack ) - 1 ];
		$key = $elt->getNoahKey();
		$noahElt = $table[$key];
		if ( $noahElt === $elt ) {
			if ( $noahElt->nextNoah ) {
				$table[$key] = $noahElt->nextNoah;
				$noahElt->nextNoah = null;
			} else {
				unset( $table[$key] );
			}
		} else {
			do {
				$prevNoahElt = $noahElt;
				$noahElt = $prevNoahElt->nextNoah;
				if ( $noahElt === $elt ) {
					// Found it, unlink
					$prevNoahElt->nextNoah = $elt->nextNoah;
					$elt->nextNoah = null;
					break;
				}
			} while ( $noahElt );
		}
	}

	/**
	 * Find element $a in the list and replace it with element $b
	 *
	 * @param FormattingElement $a
	 * @param FormattingElement $b
	 */
	public function replace( FormattingElement $a, FormattingElement $b ) {
		'@phan-var Marker|Element $a'; /** @var Marker|Element $a */
		'@phan-var Marker|Element $b'; /** @var Marker|Element $b */
		if ( $this->head !== $a && !$a->prevAFE ) {
			throw new TreeBuilderError(
				"Attempted to replace an element which is not in the AFE list" );
		}
		// Update head and tail pointers
		if ( $this->head === $a ) {
			$this->head = $b;
		}
		if ( $this->tail === $a ) {
			$this->tail = $b;
		}
		// Update previous element
		if ( $a->prevAFE ) {
			$a->prevAFE->nextAFE = $b;
		}
		// Update next element
		if ( $a->nextAFE ) {
			$a->nextAFE->prevAFE = $b;
		}
		$b->prevAFE = $a->prevAFE;
		$b->nextAFE = $a->nextAFE;
		$a->nextAFE = $a->prevAFE = null;
		// Update Noah list
		if ( $a instanceof Element ) {
			$this->removeFromNoahList( $a );
		}
		if ( $b instanceof Element ) {
			$this->addToNoahList( $b );
		}
	}

	/**
	 * Find $a in the list and insert $b after it.
	 *
	 * @param FormattingElement $a
	 * @param FormattingElement $b
	 */
	public function insertAfter( FormattingElement $a, FormattingElement $b ) {
		'@phan-var Marker|Element $a'; /** @var Marker|Element $a */
		'@phan-var Marker|Element $b'; /** @var Marker|Element $b */
		if ( $this->head !== $a && !$a->prevAFE ) {
			throw new TreeBuilderError(
				"Attempted to insert after an element which is not in the AFE list" );
		}
		if ( $this->tail === $a ) {
			$this->tail = $b;
		}
		if ( $a->nextAFE ) {
			$a->nextAFE->prevAFE = $b;
		}
		$b->nextAFE = $a->nextAFE;
		$b->prevAFE = $a;
		$a->nextAFE = $b;
		if ( $b instanceof Element ) {
			$this->addToNoahList( $b );
		}
	}

	/**
	 * Get a string representation of the AFE list, for debugging
	 * @return string
	 */
	public function dump() {
		$prev = null;
		$s = '';
		for ( $node = $this->head; $node; $prev = $node, $node = $node->nextAFE ) {
			if ( $node instanceof Marker ) {
				$s .= "MARKER\n";
				continue;
			}
			/** @var Element $node */
			$s .= $node->getDebugTag();
			if ( $node->nextNoah ) {
				$s .= " (noah sibling: " . $node->nextNoah->getDebugTag() . ')';
			}
			if ( $node->nextAFE && $node->nextAFE->prevAFE !== $node ) {
				$s .= " (reverse link is wrong!)";
			}
			$s .= "\n";
		}
		if ( $prev !== $this->tail ) {
			$s .= "(tail pointer is wrong!)\n";
		}
		return $s;
	}

	/**
	 * Get the most recently inserted element in the list
	 * @return Element|null
	 */
	public function getTail() {
		return $this->tail;
	}
}

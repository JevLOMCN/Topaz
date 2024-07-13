<?php

namespace MediaWiki\Extension\Notifications\Iterator;

use ArrayIterator;
use Iterator;
use RecursiveIterator;

/**
 * Presents a list of iterators as a single stream of results
 * when wrapped with the RecursiveIteratorIterator.
 *
 * This differs from the SPL MultipleIterator in the following ways:
 * * Does not return null for non-valid child iterators
 * * implements RecursiveIterator
 * * Lots less features(e.g. simple!)
 */
class MultipleIterator implements RecursiveIterator {
	/** @var Iterator[] */
	protected $active = [];
	/** @var array */
	protected $children;
	/** @var int */
	protected $key = 0;

	public function __construct( array $children ) {
		$this->children = $children;
	}

	public function rewind(): void {
		$this->active = $this->children;
		$this->key = 0;
		foreach ( $this->active as $key => $it ) {
			$it->rewind();
			if ( !$it->valid() ) {
				unset( $this->active[$key] );
			}
		}
	}

	public function valid(): bool {
		return (bool)$this->active;
	}

	public function next(): void {
		$this->key++;
		foreach ( $this->active as $key => $it ) {
			$it->next();
			if ( !$it->valid() ) {
				unset( $this->active[$key] );
			}
		}
	}

	#[\ReturnTypeWillChange]
	public function current() {
		$result = [];
		foreach ( $this->active as $it ) {
			$result[] = $it->current();
		}

		return $result;
	}

	public function key(): int {
		return $this->key;
	}

	public function hasChildren(): bool {
		return (bool)$this->active;
	}

	public function getChildren(): ?RecursiveIterator {
		// The NotRecursiveIterator is used rather than a RecursiveArrayIterator
		// so that nested arrays don't get recursed.
		return new NotRecursiveIterator( new ArrayIterator( $this->current() ) );
	}
}

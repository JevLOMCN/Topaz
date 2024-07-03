<?php

namespace MediaWiki\Extension\Notifications\Iterator;

use IteratorDecorator;
use RecursiveIterator;

/**
 * Wraps a non-recursive iterator with methods to be recursive
 * without children.
 *
 * Alternatively wraps a recursive iterator to prevent recursing deeper
 * than the wrapped iterator.
 */
class NotRecursiveIterator extends IteratorDecorator implements RecursiveIterator {
	public function hasChildren(): bool {
		return false;
	}

	public function getChildren(): ?RecursiveIterator {
		// @phan-suppress-next-line PhanTypeMismatchReturnProbablyReal Never called
		return null;
	}
}

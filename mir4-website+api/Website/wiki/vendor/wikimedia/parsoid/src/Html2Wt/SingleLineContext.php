<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Html2Wt;

/**
 * Stack and helpers to enforce single-line context while serializing.
 */
class SingleLineContext {
	// PORT-TODO document

	private array $stack = [];

	public function enforce(): void {
		$this->stack[] = true;
	}

	public function enforced(): bool {
		return count( $this->stack ) > 0 && end( $this->stack );
	}

	public function disable(): void {
		$this->stack[] = false;
	}

	public function pop(): void {
		array_pop( $this->stack );
	}

}

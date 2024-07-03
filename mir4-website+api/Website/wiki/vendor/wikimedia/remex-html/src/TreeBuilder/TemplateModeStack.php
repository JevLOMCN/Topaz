<?php

namespace Wikimedia\RemexHtml\TreeBuilder;

/**
 * The stack of template insertion modes. We use a storage model optimised for
 * access to the element at the top of the stack, which is stored separately
 * from the rest of the stack.
 */
class TemplateModeStack {
	/**
	 * The insertion mode at the top of the stack. This is public for
	 * performance reasons but should be treated as read-only.
	 * @var int|null
	 */
	public $current;

	/**
	 * The remainder of the stack
	 *
	 * @var array
	 */
	private $nonCurrentModes = [];

	/**
	 * Push a mode on to the stack
	 * @param int $mode
	 */
	public function push( $mode ) {
		$this->nonCurrentModes[] = $this->current;
		$this->current = $mode;
	}

	/**
	 * Pop a mode from the stack
	 */
	public function pop() {
		$this->current = array_pop( $this->nonCurrentModes );
	}

	/**
	 * Return true if the stack is empty, false otherwise
	 * @return bool
	 */
	public function isEmpty() {
		return $this->current === null;
	}
}

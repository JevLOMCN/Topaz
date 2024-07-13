<?php

namespace Wikimedia\RemexHtml\TreeBuilder;

/**
 * The parent class for the "stack of open elements".
 * @see CachingStack
 * @see SimpleStack
 */
abstract class Stack {
	public $current;

	/**
	 * Push an element
	 *
	 * @param Element $elt
	 */
	abstract public function push( Element $elt );

	/**
	 * Pop an element from the stack
	 *
	 * @return Element|null
	 */
	abstract public function pop();

	/**
	 * Replace an element which is on the stack with another element of the
	 * same type (i.e. with the same tag name).
	 *
	 * @param Element $old The element to be removed
	 * @param Element $new The element to be inserted
	 */
	abstract public function replace( Element $old, Element $new );

	/**
	 * Remove an element, potentially from the middle of the stack.
	 *
	 * @param Element $elt
	 */
	abstract public function remove( Element $elt );

	/**
	 * Is there an element in (default) scope which is in the HTML namespace
	 * and has the given tag name?
	 *
	 * @param string $name
	 * @return bool
	 */
	abstract public function isInScope( $name );

	/**
	 * Is the given element in (default) scope?
	 *
	 * @param Element $elt
	 * @return bool
	 */
	abstract public function isElementInScope( Element $elt );

	/**
	 * Is there any element in the (default) scope which is in the HTML
	 * namespace and has one of the given tag names?
	 *
	 * @param array<string,mixed> $names An array with the tag names in the
	 *   keys, the value arbitrary
	 * @return bool
	 */
	abstract public function isOneOfSetInScope( $names );

	/**
	 * Is there an element in list scope which is an HTML element with the
	 * given name?
	 *
	 * @param string $name
	 * @return bool
	 */
	abstract public function isInListScope( $name );

	/**
	 * Is there an element in button scope which is an HTML element with the
	 * given name?
	 *
	 * @param string $name
	 * @return bool
	 */
	abstract public function isInButtonScope( $name );

	/**
	 * Is there an element in table scope which is an HTML element with the
	 * given name?
	 *
	 * @param string $name
	 * @return bool
	 */
	abstract public function isInTableScope( $name );

	/**
	 * Is there an element in select scope which is an HTML element with the
	 * given name?
	 *
	 * @param string $name
	 * @return bool
	 */
	abstract public function isInSelectScope( $name );

	/**
	 * Get an element from the stack, where 0 is the first element inserted,
	 * and $this->length() - 1 is the most recently inserted element. This will
	 * raise a PHP notice if the index is out of range.
	 *
	 * @param int $idx
	 * @return Element|null
	 */
	abstract public function item( $idx );

	/**
	 * Get the number of elements in the stack.
	 *
	 * @return int
	 */
	abstract public function length();

	/**
	 * Is there a template element in the stack of open elements?
	 *
	 * @return bool
	 */
	abstract public function hasTemplate();

	/**
	 * Get a string representation of the stack for debugging purposes.
	 *
	 * @return string
	 */
	public function dump() {
		$s = '';
		for ( $i = 0; $i < $this->length(); $i++ ) {
			$item = $this->item( $i );
			$s .= "$i. " . $item->getDebugTag();
			if ( $i === $this->length() - 1 && $item !== $this->current ) {
				$s .= " CURRENT POINTER INCORRECT";
			}
			$s .= "\n";
		}
		return $s;
	}
}

<?php

namespace Wikimedia\RemexHtml\Tokenizer;

/**
 * Interface for attributes emitted by the tokenizer
 */
interface Attributes extends \ArrayAccess, \IteratorAggregate {
	/**
	 * Get the attributes as a key/value array
	 * @return string[]
	 */
	public function getValues();

	/**
	 * Get the attributes as an array of Attribute objects
	 * @return Attribute[]
	 */
	public function getObjects();

	/**
	 * Get the number of attributes. This may include duplicates, and so may
	 * be larger than count( $this->getValues() ). Including duplicates
	 * gives us an efficient way to distinguish zero attributes from non-zero
	 * but is not compliant with the spec, which states that duplicate
	 * attributes must be removed.
	 *
	 * @return int
	 */
	public function count();

	/**
	 * Add any attributes in the argument to $this, but if an attribute of the
	 * same name already exists, do not overwrite it.
	 *
	 * @param Attributes $other
	 */
	public function merge( Attributes $other );

	/**
	 * It's efficient to assume that attributes are immutable when cloning
	 * nodes due to AFE reconstruction or AAA. So by default, this returns
	 * $this. But users can override it if attribute cloning needs special
	 * handling.
	 *
	 * @return Attributes
	 */
	public function clone();
}

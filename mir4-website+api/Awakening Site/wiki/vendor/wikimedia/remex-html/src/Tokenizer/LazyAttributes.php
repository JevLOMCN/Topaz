<?php

namespace Wikimedia\RemexHtml\Tokenizer;

/**
 * An Attributes implementation which defers interpretation of regex match
 * results until the caller requires them.
 *
 * This should not be directly instantiated outside of Tokenizer.
 */
class LazyAttributes implements Attributes {
	/** @var callable */
	private $interpreter;

	/** @var mixed */
	private $data;

	/** @var string[] */
	private $attributes;

	/** @var Attribute[] */
	private $attrObjects;

	public function __construct( $data, callable $interpreter ) {
		$this->interpreter = $interpreter;
		$this->data = $data;
	}

	/**
	 * Initialize the attributes array
	 */
	private function init() {
		if ( $this->attributes === null ) {
			$func = $this->interpreter;
			$this->attributes = $func( $this->data );
			// @phan-suppress-next-line PhanTypeMismatchPropertyProbablyReal
			$this->interpreter = null;
			$this->data = null;
		}
	}

	public function offsetExists( $offset ): bool {
		if ( $this->attributes === null ) {
			$this->init();
		}
		return isset( $this->attributes[$offset] );
	}

	public function &offsetGet( $offset ): string {
		if ( $this->attributes === null ) {
			$this->init();
		}
		return $this->attributes[$offset];
	}

	public function offsetSet( $offset, $value ): void {
		if ( $this->attributes === null ) {
			$this->init();
		}
		$this->attributes[$offset] = $value;
		if ( $this->attrObjects !== null ) {
			$this->attrObjects[$offset] = new Attribute( $offset, null, null, $offset, $value );
		}
	}

	public function offsetUnset( $offset ): void {
		if ( $this->attributes === null ) {
			$this->init();
		}
		unset( $this->attributes[$offset] );
		unset( $this->attrObjects[$offset] );
	}

	public function getValues() {
		if ( $this->attributes === null ) {
			$this->init();
		}
		return $this->attributes;
	}

	public function getObjects() {
		if ( $this->attrObjects === null ) {
			if ( $this->attributes === null ) {
				$this->init();
			}
			$result = [];
			foreach ( $this->attributes as $name => $value ) {
				$result[$name] = new Attribute( $name, null, null, $name, $value );
			}
			$this->attrObjects = $result;
		}
		return $this->attrObjects;
	}

	public function count(): int {
		if ( $this->attributes === null ) {
			return count( $this->data );
		}
		return count( $this->attributes );
	}

	public function getIterator(): \ArrayIterator {
		if ( $this->attributes === null ) {
			$this->init();
		}
		return new \ArrayIterator( $this->attributes );
	}

	public function merge( Attributes $other ) {
		if ( $this->attributes === null ) {
			$this->init();
		}
		foreach ( $other as $name => $value ) {
			if ( !isset( $this->attributes[$name] ) ) {
				$this->attributes[$name] = $value;
			}
		}
	}

	public function clone() {
		return $this;
	}
}

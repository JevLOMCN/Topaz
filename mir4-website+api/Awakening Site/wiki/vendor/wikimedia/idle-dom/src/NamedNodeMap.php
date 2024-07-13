<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * NamedNodeMap
 *
 * @see https://dom.spec.whatwg.org/#interface-namednodemap
 *
 * @property int $length
 * @phan-forbid-undeclared-magic-properties
 */
interface NamedNodeMap extends \ArrayAccess, \IteratorAggregate, \Countable {
	/**
	 * @return int
	 */
	public function getLength(): int;

	/**
	 * @param int $index
	 * @return Attr|null
	 */
	public function item( int $index );

	/**
	 * @param string $qualifiedName
	 * @return Attr|null
	 */
	public function getNamedItem( string $qualifiedName );

	/**
	 * @param ?string $namespace
	 * @param string $localName
	 * @return Attr|null
	 */
	public function getNamedItemNS( ?string $namespace, string $localName );

	/**
	 * @param Attr $attr
	 * @return Attr|null
	 */
	public function setNamedItem( /* Attr */ $attr );

	/**
	 * @param Attr $attr
	 * @return Attr|null
	 */
	public function setNamedItemNS( /* Attr */ $attr );

	/**
	 * @param string $qualifiedName
	 * @return Attr
	 */
	public function removeNamedItem( string $qualifiedName );

	/**
	 * @param ?string $namespace
	 * @param string $localName
	 * @return Attr
	 */
	public function removeNamedItemNS( ?string $namespace, string $localName );

	/**
	 * @return \Iterator<Attr> Value iterator returning Attr items
	 */
	public function getIterator();

}

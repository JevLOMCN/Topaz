<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * URLSearchParams
 *
 * @see https://dom.spec.whatwg.org/#interface-urlsearchparams
 *
 * @phan-forbid-undeclared-magic-properties
 */
interface URLSearchParams extends \IteratorAggregate {

	/**
	 * @param string $name
	 * @param string $value
	 * @return void
	 */
	public function append( string $name, string $value ): void;

	/**
	 * @param string $name
	 * @return void
	 */
	public function delete( string $name ): void;

	/**
	 * @param string $name
	 * @return ?string
	 */
	public function get( string $name ): ?string;

	/**
	 * @param string $name
	 * @return list<string>
	 */
	public function getAll( string $name ): array;

	/**
	 * @param string $name
	 * @return bool
	 */
	public function has( string $name ): bool;

	/**
	 * @param string $name
	 * @param string $value
	 * @return void
	 */
	public function set( string $name, string $value ): void;

	/**
	 * @return void
	 */
	public function sort(): void;

	/**
	 * @return \Iterator<string,string> Pair iterator: string => string
	 */
	public function getIterator();

	/**
	 * @return string
	 */
	public function toString(): string;

}

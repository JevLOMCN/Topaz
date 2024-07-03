<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * DOMStringMap
 *
 * @see https://dom.spec.whatwg.org/#interface-domstringmap
 *
 * @phan-forbid-undeclared-magic-properties
 */
interface DOMStringMap extends \ArrayAccess {
	/**
	 * @param string $name
	 * @return string
	 */
	public function namedItem( string $name ): string;

	/**
	 * @param string $name
	 * @param string $value
	 * @return void
	 */
	public function setNamedItem( string $name, string $value ): void;

	/**
	 * @param string $name
	 * @return void
	 */
	public function removeNamedItem( string $name ): void;

}

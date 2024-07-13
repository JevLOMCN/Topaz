<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Stub;

use Exception;

trait URLSearchParams {

	// Underscore is used to avoid conflicts with DOM-reserved names
	// phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
	// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

	/**
	 * @return Exception
	 */
	abstract protected function _unimplemented(): Exception;

	// phpcs:enable

	/**
	 * @param string $name
	 * @param string $value
	 * @return void
	 */
	public function append( string $name, string $value ): void {
		throw self::_unimplemented();
	}

	/**
	 * @param string $name
	 * @return void
	 */
	public function delete( string $name ): void {
		throw self::_unimplemented();
	}

	/**
	 * @param string $name
	 * @return ?string
	 */
	public function get( string $name ): ?string {
		throw self::_unimplemented();
	}

	/**
	 * @param string $name
	 * @return list<string>
	 */
	public function getAll( string $name ): array {
		throw self::_unimplemented();
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function has( string $name ): bool {
		throw self::_unimplemented();
	}

	/**
	 * @param string $name
	 * @param string $value
	 * @return void
	 */
	public function set( string $name, string $value ): void {
		throw self::_unimplemented();
	}

	/**
	 * @return void
	 */
	public function sort(): void {
		throw self::_unimplemented();
	}

	/**
	 * @return \Iterator<string,string> Pair iterator: string => string
	 */
	public function getIterator() {
		throw self::_unimplemented();
	}

	/**
	 * @return string
	 */
	public function toString(): string {
		throw self::_unimplemented();
	}

}

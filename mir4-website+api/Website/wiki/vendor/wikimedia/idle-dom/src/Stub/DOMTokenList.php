<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Stub;

use Exception;

trait DOMTokenList {

	// Underscore is used to avoid conflicts with DOM-reserved names
	// phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
	// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

	/**
	 * @return Exception
	 */
	abstract protected function _unimplemented(): Exception;

	// phpcs:enable

	/**
	 * @return int
	 */
	public function getLength(): int {
		throw self::_unimplemented();
	}

	/**
	 * @param int $index
	 * @return ?string
	 */
	public function item( int $index ): ?string {
		throw self::_unimplemented();
	}

	/**
	 * @param string $token
	 * @return bool
	 */
	public function contains( string $token ): bool {
		throw self::_unimplemented();
	}

	/**
	 * @param string ...$tokens
	 * @return void
	 */
	public function add( string ...$tokens ): void {
		throw self::_unimplemented();
	}

	/**
	 * @param string ...$tokens
	 * @return void
	 */
	public function remove( string ...$tokens ): void {
		throw self::_unimplemented();
	}

	/**
	 * @param string $token
	 * @param ?bool $force
	 * @return bool
	 */
	public function toggle( string $token, ?bool $force = null ): bool {
		throw self::_unimplemented();
	}

	/**
	 * @param string $token
	 * @param string $newToken
	 * @return bool
	 */
	public function replace( string $token, string $newToken ): bool {
		throw self::_unimplemented();
	}

	/**
	 * @param string $token
	 * @return bool
	 */
	public function supports( string $token ): bool {
		throw self::_unimplemented();
	}

	/**
	 * @return string
	 */
	public function getValue(): string {
		throw self::_unimplemented();
	}

	/**
	 * @param string $val
	 */
	public function setValue( string $val ): void {
		throw self::_unimplemented();
	}

}

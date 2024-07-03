<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Stub;

use Exception;

trait MediaList {

	// Underscore is used to avoid conflicts with DOM-reserved names
	// phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
	// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

	/**
	 * @return Exception
	 */
	abstract protected function _unimplemented(): Exception;

	// phpcs:enable

	/**
	 * @return string
	 */
	public function getMediaText(): string {
		throw self::_unimplemented();
	}

	/**
	 * @param ?string $val
	 */
	public function setMediaText( ?string $val ): void {
		throw self::_unimplemented();
	}

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
	 * @param string $medium
	 * @return void
	 */
	public function appendMedium( string $medium ): void {
		throw self::_unimplemented();
	}

	/**
	 * @param string $medium
	 * @return void
	 */
	public function deleteMedium( string $medium ): void {
		throw self::_unimplemented();
	}

}

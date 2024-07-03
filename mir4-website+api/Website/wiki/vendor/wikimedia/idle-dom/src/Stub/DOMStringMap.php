<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Stub;

use Exception;

trait DOMStringMap {

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
	 * @return string
	 */
	public function namedItem( string $name ): string {
		throw self::_unimplemented();
	}

	/**
	 * @param string $name
	 * @param string $value
	 * @return void
	 */
	public function setNamedItem( string $name, string $value ): void {
		throw self::_unimplemented();
	}

	/**
	 * @param string $name
	 * @return void
	 */
	public function removeNamedItem( string $name ): void {
		throw self::_unimplemented();
	}

}

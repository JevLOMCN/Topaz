<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Stub;

use Exception;

trait DOMException {

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
	public function getName(): string {
		throw self::_unimplemented();
	}

	/**
	 * @return string
	 */
	public function getMessage() {
		throw self::_unimplemented();
	}

	/**
	 * @return int
	 */
	public function getCode() {
		throw self::_unimplemented();
	}

}

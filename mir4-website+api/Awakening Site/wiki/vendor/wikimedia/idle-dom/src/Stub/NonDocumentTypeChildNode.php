<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Stub;

use Exception;
use Wikimedia\IDLeDOM\Element;

trait NonDocumentTypeChildNode {

	// Underscore is used to avoid conflicts with DOM-reserved names
	// phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
	// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

	/**
	 * @return Exception
	 */
	abstract protected function _unimplemented(): Exception;

	// phpcs:enable

	/**
	 * @return Element|null
	 */
	public function getPreviousElementSibling() {
		throw self::_unimplemented();
	}

	/**
	 * @return Element|null
	 */
	public function getNextElementSibling() {
		throw self::_unimplemented();
	}

}

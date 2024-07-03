<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Stub;

use Exception;
use Wikimedia\IDLeDOM\HTMLSlotElement;

trait Slottable {

	// Underscore is used to avoid conflicts with DOM-reserved names
	// phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
	// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

	/**
	 * @return Exception
	 */
	abstract protected function _unimplemented(): Exception;

	// phpcs:enable

	/**
	 * @return HTMLSlotElement|null
	 */
	public function getAssignedSlot() {
		throw self::_unimplemented();
	}

}

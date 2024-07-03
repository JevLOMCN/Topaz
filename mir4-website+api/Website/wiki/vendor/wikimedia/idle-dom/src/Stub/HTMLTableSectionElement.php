<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Stub;

use Exception;
use Wikimedia\IDLeDOM\HTMLCollection;
use Wikimedia\IDLeDOM\HTMLElement;

trait HTMLTableSectionElement {

	// Underscore is used to avoid conflicts with DOM-reserved names
	// phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
	// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

	/**
	 * @return Exception
	 */
	abstract protected function _unimplemented(): Exception;

	// phpcs:enable

	/**
	 * @return HTMLCollection
	 */
	public function getRows() {
		throw self::_unimplemented();
	}

	/**
	 * @param int $index
	 * @return HTMLElement
	 */
	public function insertRow( int $index = -1 ) {
		throw self::_unimplemented();
	}

	/**
	 * @param int $index
	 * @return void
	 */
	public function deleteRow( int $index ): void {
		throw self::_unimplemented();
	}

}

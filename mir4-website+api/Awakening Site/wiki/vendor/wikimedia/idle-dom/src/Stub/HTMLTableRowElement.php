<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Stub;

use Exception;
use Wikimedia\IDLeDOM\HTMLCollection;
use Wikimedia\IDLeDOM\HTMLTableCellElement;

trait HTMLTableRowElement {

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
	public function getRowIndex(): int {
		throw self::_unimplemented();
	}

	/**
	 * @return int
	 */
	public function getSectionRowIndex(): int {
		throw self::_unimplemented();
	}

	/**
	 * @return HTMLCollection
	 */
	public function getCells() {
		throw self::_unimplemented();
	}

	/**
	 * @param int $index
	 * @return HTMLTableCellElement
	 */
	public function insertCell( int $index = -1 ) {
		throw self::_unimplemented();
	}

	/**
	 * @param int $index
	 * @return void
	 */
	public function deleteCell( int $index ): void {
		throw self::_unimplemented();
	}

}

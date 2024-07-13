<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Stub;

use Exception;
use Wikimedia\IDLeDOM\Node;

trait XPathResult {

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
	public function getResultType(): int {
		throw self::_unimplemented();
	}

	/**
	 * @return float
	 */
	public function getNumberValue(): float {
		throw self::_unimplemented();
	}

	/**
	 * @return string
	 */
	public function getStringValue(): string {
		throw self::_unimplemented();
	}

	/**
	 * @return bool
	 */
	public function getBooleanValue(): bool {
		throw self::_unimplemented();
	}

	/**
	 * @return Node|null
	 */
	public function getSingleNodeValue() {
		throw self::_unimplemented();
	}

	/**
	 * @return bool
	 */
	public function getInvalidIteratorState(): bool {
		throw self::_unimplemented();
	}

	/**
	 * @return int
	 */
	public function getSnapshotLength(): int {
		throw self::_unimplemented();
	}

	/**
	 * @return Node|null
	 */
	public function iterateNext() {
		throw self::_unimplemented();
	}

	/**
	 * @param int $index
	 * @return Node|null
	 */
	public function snapshotItem( int $index ) {
		throw self::_unimplemented();
	}

}

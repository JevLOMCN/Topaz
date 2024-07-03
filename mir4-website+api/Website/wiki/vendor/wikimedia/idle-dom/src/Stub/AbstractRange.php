<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Stub;

use Exception;
use Wikimedia\IDLeDOM\Node;

trait AbstractRange {

	// Underscore is used to avoid conflicts with DOM-reserved names
	// phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
	// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

	/**
	 * @return Exception
	 */
	abstract protected function _unimplemented(): Exception;

	// phpcs:enable

	/**
	 * @return Node
	 */
	public function getStartContainer() {
		throw self::_unimplemented();
	}

	/**
	 * @return int
	 */
	public function getStartOffset(): int {
		throw self::_unimplemented();
	}

	/**
	 * @return Node
	 */
	public function getEndContainer() {
		throw self::_unimplemented();
	}

	/**
	 * @return int
	 */
	public function getEndOffset(): int {
		throw self::_unimplemented();
	}

	/**
	 * @return bool
	 */
	public function getCollapsed(): bool {
		throw self::_unimplemented();
	}

}

<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Stub;

use Exception;
use Wikimedia\IDLeDOM\Node;
use Wikimedia\IDLeDOM\NodeFilter;

trait NodeIterator {

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
	public function getRoot() {
		throw self::_unimplemented();
	}

	/**
	 * @return Node
	 */
	public function getReferenceNode() {
		throw self::_unimplemented();
	}

	/**
	 * @return bool
	 */
	public function getPointerBeforeReferenceNode(): bool {
		throw self::_unimplemented();
	}

	/**
	 * @return int
	 */
	public function getWhatToShow(): int {
		throw self::_unimplemented();
	}

	/**
	 * @return NodeFilter|callable|null
	 */
	public function getFilter() {
		throw self::_unimplemented();
	}

	/**
	 * @return Node|null
	 */
	public function nextNode() {
		throw self::_unimplemented();
	}

	/**
	 * @return Node|null
	 */
	public function previousNode() {
		throw self::_unimplemented();
	}

	/**
	 * @return void
	 */
	public function detach(): void {
		throw self::_unimplemented();
	}

}

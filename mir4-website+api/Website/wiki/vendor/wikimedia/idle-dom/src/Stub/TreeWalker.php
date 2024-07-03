<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Stub;

use Exception;
use Wikimedia\IDLeDOM\Node;
use Wikimedia\IDLeDOM\NodeFilter;

trait TreeWalker {

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
	 * @return Node
	 */
	public function getCurrentNode() {
		throw self::_unimplemented();
	}

	/**
	 * @param Node $val
	 */
	public function setCurrentNode( /* Node */ $val ): void {
		throw self::_unimplemented();
	}

	/**
	 * @return Node|null
	 */
	public function parentNode() {
		throw self::_unimplemented();
	}

	/**
	 * @return Node|null
	 */
	public function firstChild() {
		throw self::_unimplemented();
	}

	/**
	 * @return Node|null
	 */
	public function lastChild() {
		throw self::_unimplemented();
	}

	/**
	 * @return Node|null
	 */
	public function previousSibling() {
		throw self::_unimplemented();
	}

	/**
	 * @return Node|null
	 */
	public function nextSibling() {
		throw self::_unimplemented();
	}

	/**
	 * @return Node|null
	 */
	public function previousNode() {
		throw self::_unimplemented();
	}

	/**
	 * @return Node|null
	 */
	public function nextNode() {
		throw self::_unimplemented();
	}

}

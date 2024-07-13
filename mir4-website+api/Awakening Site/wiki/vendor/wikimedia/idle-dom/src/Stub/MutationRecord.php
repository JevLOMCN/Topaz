<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Stub;

use Exception;
use Wikimedia\IDLeDOM\Node;
use Wikimedia\IDLeDOM\NodeList;

trait MutationRecord {

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
	public function getType(): string {
		throw self::_unimplemented();
	}

	/**
	 * @return Node
	 */
	public function getTarget() {
		throw self::_unimplemented();
	}

	/**
	 * @return NodeList
	 */
	public function getAddedNodes() {
		throw self::_unimplemented();
	}

	/**
	 * @return NodeList
	 */
	public function getRemovedNodes() {
		throw self::_unimplemented();
	}

	/**
	 * @return Node|null
	 */
	public function getPreviousSibling() {
		throw self::_unimplemented();
	}

	/**
	 * @return Node|null
	 */
	public function getNextSibling() {
		throw self::_unimplemented();
	}

	/**
	 * @return ?string
	 */
	public function getAttributeName(): ?string {
		throw self::_unimplemented();
	}

	/**
	 * @return ?string
	 */
	public function getAttributeNamespace(): ?string {
		throw self::_unimplemented();
	}

	/**
	 * @return ?string
	 */
	public function getOldValue(): ?string {
		throw self::_unimplemented();
	}

}

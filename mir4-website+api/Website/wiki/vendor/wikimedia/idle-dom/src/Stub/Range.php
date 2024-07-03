<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM\Stub;

use Exception;
use Wikimedia\IDLeDOM\DocumentFragment;
use Wikimedia\IDLeDOM\Node;

trait Range {

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
	public function getCommonAncestorContainer() {
		throw self::_unimplemented();
	}

	/**
	 * @param Node $node
	 * @param int $offset
	 * @return void
	 */
	public function setStart( /* Node */ $node, int $offset ): void {
		throw self::_unimplemented();
	}

	/**
	 * @param Node $node
	 * @param int $offset
	 * @return void
	 */
	public function setEnd( /* Node */ $node, int $offset ): void {
		throw self::_unimplemented();
	}

	/**
	 * @param Node $node
	 * @return void
	 */
	public function setStartBefore( /* Node */ $node ): void {
		throw self::_unimplemented();
	}

	/**
	 * @param Node $node
	 * @return void
	 */
	public function setStartAfter( /* Node */ $node ): void {
		throw self::_unimplemented();
	}

	/**
	 * @param Node $node
	 * @return void
	 */
	public function setEndBefore( /* Node */ $node ): void {
		throw self::_unimplemented();
	}

	/**
	 * @param Node $node
	 * @return void
	 */
	public function setEndAfter( /* Node */ $node ): void {
		throw self::_unimplemented();
	}

	/**
	 * @param bool $toStart
	 * @return void
	 */
	public function collapse( bool $toStart = false ): void {
		throw self::_unimplemented();
	}

	/**
	 * @param Node $node
	 * @return void
	 */
	public function selectNode( /* Node */ $node ): void {
		throw self::_unimplemented();
	}

	/**
	 * @param Node $node
	 * @return void
	 */
	public function selectNodeContents( /* Node */ $node ): void {
		throw self::_unimplemented();
	}

	/**
	 * @param int $how
	 * @param \Wikimedia\IDLeDOM\Range $sourceRange
	 * @return int
	 */
	public function compareBoundaryPoints( int $how, /* \Wikimedia\IDLeDOM\Range */ $sourceRange ): int {
		throw self::_unimplemented();
	}

	/**
	 * @return void
	 */
	public function deleteContents(): void {
		throw self::_unimplemented();
	}

	/**
	 * @return DocumentFragment
	 */
	public function extractContents() {
		throw self::_unimplemented();
	}

	/**
	 * @return DocumentFragment
	 */
	public function cloneContents() {
		throw self::_unimplemented();
	}

	/**
	 * @param Node $node
	 * @return void
	 */
	public function insertNode( /* Node */ $node ): void {
		throw self::_unimplemented();
	}

	/**
	 * @param Node $newParent
	 * @return void
	 */
	public function surroundContents( /* Node */ $newParent ): void {
		throw self::_unimplemented();
	}

	/**
	 * @return \Wikimedia\IDLeDOM\Range
	 */
	public function cloneRange() {
		throw self::_unimplemented();
	}

	/**
	 * @return void
	 */
	public function detach(): void {
		throw self::_unimplemented();
	}

	/**
	 * @param Node $node
	 * @param int $offset
	 * @return bool
	 */
	public function isPointInRange( /* Node */ $node, int $offset ): bool {
		throw self::_unimplemented();
	}

	/**
	 * @param Node $node
	 * @param int $offset
	 * @return int
	 */
	public function comparePoint( /* Node */ $node, int $offset ): int {
		throw self::_unimplemented();
	}

	/**
	 * @param Node $node
	 * @return bool
	 */
	public function intersectsNode( /* Node */ $node ): bool {
		throw self::_unimplemented();
	}

	/**
	 * @return string
	 */
	public function toString(): string {
		throw self::_unimplemented();
	}

	/**
	 * @param string $fragment
	 * @return DocumentFragment
	 */
	public function createContextualFragment( string $fragment ) {
		throw self::_unimplemented();
	}

}

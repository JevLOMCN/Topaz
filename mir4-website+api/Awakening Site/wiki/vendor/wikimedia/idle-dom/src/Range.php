<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * Range
 *
 * @see https://dom.spec.whatwg.org/#interface-range
 *
 * @property Node $startContainer
 * @property int $startOffset
 * @property Node $endContainer
 * @property int $endOffset
 * @property bool $collapsed
 * @property Node $commonAncestorContainer
 * @phan-forbid-undeclared-magic-properties
 */
interface Range extends AbstractRange {
	// Direct parent: AbstractRange

	/**
	 * @return Node
	 */
	public function getCommonAncestorContainer();

	/**
	 * @param Node $node
	 * @param int $offset
	 * @return void
	 */
	public function setStart( /* Node */ $node, int $offset ): void;

	/**
	 * @param Node $node
	 * @param int $offset
	 * @return void
	 */
	public function setEnd( /* Node */ $node, int $offset ): void;

	/**
	 * @param Node $node
	 * @return void
	 */
	public function setStartBefore( /* Node */ $node ): void;

	/**
	 * @param Node $node
	 * @return void
	 */
	public function setStartAfter( /* Node */ $node ): void;

	/**
	 * @param Node $node
	 * @return void
	 */
	public function setEndBefore( /* Node */ $node ): void;

	/**
	 * @param Node $node
	 * @return void
	 */
	public function setEndAfter( /* Node */ $node ): void;

	/**
	 * @param bool $toStart
	 * @return void
	 */
	public function collapse( bool $toStart = false ): void;

	/**
	 * @param Node $node
	 * @return void
	 */
	public function selectNode( /* Node */ $node ): void;

	/**
	 * @param Node $node
	 * @return void
	 */
	public function selectNodeContents( /* Node */ $node ): void;

	/** @var int */
	public const START_TO_START = 0;

	/** @var int */
	public const START_TO_END = 1;

	/** @var int */
	public const END_TO_END = 2;

	/** @var int */
	public const END_TO_START = 3;

	/**
	 * @param int $how
	 * @param \Wikimedia\IDLeDOM\Range $sourceRange
	 * @return int
	 */
	public function compareBoundaryPoints( int $how, /* \Wikimedia\IDLeDOM\Range */ $sourceRange ): int;

	/**
	 * @return void
	 */
	public function deleteContents(): void;

	/**
	 * @return DocumentFragment
	 */
	public function extractContents();

	/**
	 * @return DocumentFragment
	 */
	public function cloneContents();

	/**
	 * @param Node $node
	 * @return void
	 */
	public function insertNode( /* Node */ $node ): void;

	/**
	 * @param Node $newParent
	 * @return void
	 */
	public function surroundContents( /* Node */ $newParent ): void;

	/**
	 * @return \Wikimedia\IDLeDOM\Range
	 */
	public function cloneRange();

	/**
	 * @return void
	 */
	public function detach(): void;

	/**
	 * @param Node $node
	 * @param int $offset
	 * @return bool
	 */
	public function isPointInRange( /* Node */ $node, int $offset ): bool;

	/**
	 * @param Node $node
	 * @param int $offset
	 * @return int
	 */
	public function comparePoint( /* Node */ $node, int $offset ): int;

	/**
	 * @param Node $node
	 * @return bool
	 */
	public function intersectsNode( /* Node */ $node ): bool;

	/**
	 * @return string
	 */
	public function toString(): string;

	/**
	 * @param string $fragment
	 * @return DocumentFragment
	 */
	public function createContextualFragment( string $fragment );

}

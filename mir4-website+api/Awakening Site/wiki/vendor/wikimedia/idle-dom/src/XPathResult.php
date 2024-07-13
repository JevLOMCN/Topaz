<?php

// AUTOMATICALLY GENERATED.  DO NOT EDIT.
// Use `composer build` to regenerate.

namespace Wikimedia\IDLeDOM;

/**
 * XPathResult
 *
 * @see https://dom.spec.whatwg.org/#interface-xpathresult
 *
 * @property int $resultType
 * @property float $numberValue
 * @property string $stringValue
 * @property bool $booleanValue
 * @property Node|null $singleNodeValue
 * @property bool $invalidIteratorState
 * @property int $snapshotLength
 * @phan-forbid-undeclared-magic-properties
 */
interface XPathResult {
	/** @var int */
	public const ANY_TYPE = 0;

	/** @var int */
	public const NUMBER_TYPE = 1;

	/** @var int */
	public const STRING_TYPE = 2;

	/** @var int */
	public const BOOLEAN_TYPE = 3;

	/** @var int */
	public const UNORDERED_NODE_ITERATOR_TYPE = 4;

	/** @var int */
	public const ORDERED_NODE_ITERATOR_TYPE = 5;

	/** @var int */
	public const UNORDERED_NODE_SNAPSHOT_TYPE = 6;

	/** @var int */
	public const ORDERED_NODE_SNAPSHOT_TYPE = 7;

	/** @var int */
	public const ANY_UNORDERED_NODE_TYPE = 8;

	/** @var int */
	public const FIRST_ORDERED_NODE_TYPE = 9;

	/**
	 * @return int
	 */
	public function getResultType(): int;

	/**
	 * @return float
	 */
	public function getNumberValue(): float;

	/**
	 * @return string
	 */
	public function getStringValue(): string;

	/**
	 * @return bool
	 */
	public function getBooleanValue(): bool;

	/**
	 * @return Node|null
	 */
	public function getSingleNodeValue();

	/**
	 * @return bool
	 */
	public function getInvalidIteratorState(): bool;

	/**
	 * @return int
	 */
	public function getSnapshotLength(): int;

	/**
	 * @return Node|null
	 */
	public function iterateNext();

	/**
	 * @param int $index
	 * @return Node|null
	 */
	public function snapshotItem( int $index );

}

<?php

namespace MediaWiki\Extension\AbuseFilter\Filter;

/**
 * Immutable value object representing a "complete" filter. This can be used to represent filters
 * that already exist in the database, but you should probably use subclasses for that.
 */
class Filter extends AbstractFilter {
	/** @var LastEditInfo */
	protected $lastEditInfo;
	/** @var int|null Can be null if not specified */
	protected $id;
	/** @var int|null Can be null if the filter is not current */
	protected $hitCount;
	/** @var bool|null Can be null if the filter is not current */
	protected $throttled;

	/**
	 * @param Specs $specs
	 * @param Flags $flags
	 * @param callable|array[] $actions Array with params or callable that will return them
	 * @phan-param array[]|callable():array[] $actions
	 * @param LastEditInfo $lastEditInfo
	 * @param int|null $id
	 * @param int|null $hitCount
	 * @param bool|null $throttled
	 */
	public function __construct(
		Specs $specs,
		Flags $flags,
		$actions,
		LastEditInfo $lastEditInfo,
		?int $id = null,
		?int $hitCount = null,
		?bool $throttled = null
	) {
		parent::__construct( $specs, $flags, $actions );
		$this->lastEditInfo = clone $lastEditInfo;
		$this->id = $id;
		$this->hitCount = $hitCount;
		$this->throttled = $throttled;
	}

	/**
	 * @return LastEditInfo
	 */
	public function getLastEditInfo(): LastEditInfo {
		return clone $this->lastEditInfo;
	}

	/**
	 * @return int|null
	 */
	public function getID(): ?int {
		return $this->id;
	}

	/**
	 * @return int
	 */
	public function getUserID(): int {
		return $this->lastEditInfo->getUserID();
	}

	/**
	 * @return string
	 */
	public function getUserName(): string {
		return $this->lastEditInfo->getUserName();
	}

	/**
	 * @return string
	 */
	public function getTimestamp(): string {
		return $this->lastEditInfo->getTimestamp();
	}

	/**
	 * @return int|null
	 */
	public function getHitCount(): ?int {
		return $this->hitCount;
	}

	/**
	 * @return bool|null
	 */
	public function isThrottled(): ?bool {
		return $this->throttled;
	}

	/**
	 * Make sure we don't leave any (writeable) reference
	 */
	public function __clone() {
		parent::__clone();
		$this->lastEditInfo = clone $this->lastEditInfo;
	}
}

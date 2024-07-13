<?php

namespace MediaWiki\Extension\AbuseFilter\Filter;

/**
 * Variant of ExistingFilters for past versions of filters
 */
class HistoryFilter extends ExistingFilter {
	/** @var int */
	private $historyID;

	/**
	 * @param Specs $specs
	 * @param Flags $flags
	 * @param callable|array[] $actions Array with params or callable that will return them
	 * @phan-param array[]|callable():array[] $actions
	 * @param LastEditInfo $lastEditInfo
	 * @param int $id
	 * @param int $historyID
	 */
	public function __construct(
		Specs $specs,
		Flags $flags,
		$actions,
		LastEditInfo $lastEditInfo,
		int $id,
		int $historyID
	) {
		parent::__construct( $specs, $flags, $actions, $lastEditInfo, $id );
		$this->historyID = $historyID;
	}

	/**
	 * @return int
	 */
	public function getHistoryID(): int {
		return $this->historyID;
	}
}

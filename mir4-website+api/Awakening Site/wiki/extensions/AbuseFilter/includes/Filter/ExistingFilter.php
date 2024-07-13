<?php

namespace MediaWiki\Extension\AbuseFilter\Filter;

/**
 * Variant of Filter for filters that are known to exist
 */
class ExistingFilter extends Filter {
	/**
	 * @param Specs $specs
	 * @param Flags $flags
	 * @param callable|array[] $actions Array with params or callable that will return them
	 * @phan-param array[]|callable():array[] $actions
	 * @param LastEditInfo $lastEditInfo
	 * @param int $id
	 * @param int|null $hitCount
	 * @param bool|null $throttled
	 */
	public function __construct(
		Specs $specs,
		Flags $flags,
		$actions,
		LastEditInfo $lastEditInfo,
		int $id,
		?int $hitCount = null,
		?bool $throttled = null
	) {
		parent::__construct( $specs, $flags, $actions, $lastEditInfo, $id, $hitCount, $throttled );
	}

	/**
	 * @return int
	 */
	public function getID(): int {
		return $this->id;
	}
}

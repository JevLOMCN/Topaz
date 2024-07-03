<?php

namespace MediaWiki\Extension\Notifications;

use UnexpectedValueException;
use WANObjectCache;

/**
 * Caches an ContainmentList within WANObjectCache to prevent needing
 * to load the nested list from a potentially slow source (mysql, etc).
 */
class CachedList implements ContainmentList {
	private const ONE_WEEK = 4233600;

	/** @var WANObjectCache */
	protected $cache;
	/** @var string */
	protected $partialCacheKey;
	/** @var ContainmentList */
	protected $nestedList;
	/** @var int */
	protected $timeout;
	/** @var string[]|null */
	private $result;

	/**
	 * @param WANObjectCache $cache Bag to stored cached data in.
	 * @param string $partialCacheKey Partial cache key, $nestedList->getCacheKey() will be appended
	 *   to this to construct the cache key used.
	 * @param ContainmentList $nestedList The nested EchoContainmentList to cache the result of.
	 * @param int $timeout How long in seconds to cache the nested list, defaults to 1 week.
	 */
	public function __construct(
		WANObjectCache $cache,
		$partialCacheKey,
		ContainmentList $nestedList,
		$timeout = self::ONE_WEEK
	) {
		$this->cache = $cache;
		$this->partialCacheKey = $partialCacheKey;
		$this->nestedList = $nestedList;
		$this->timeout = $timeout;
	}

	/**
	 * @inheritDoc
	 */
	public function getValues() {
		if ( $this->result ) {
			return $this->result;
		}
		$this->result = $this->cache->getWithSetCallback(
			$this->getCacheKey(),
			$this->timeout,
			function () {
				$result = $this->nestedList->getValues();
				if ( !is_array( $result ) ) {
					throw new UnexpectedValueException( sprintf(
						"Expected array but received '%s' from '%s::getValues'",
						is_object( $result ) ? get_class( $result ) : gettype( $result ),
						get_class( $this->nestedList )
					) );
				}
				return $result;
			}
		);
		return $this->result;
	}

	/**
	 * @inheritDoc
	 */
	public function getCacheKey() {
		return $this->cache->makeGlobalKey(
			'echo-containment-list',
			$this->partialCacheKey,
			$this->nestedList->getCacheKey()
		);
	}
}

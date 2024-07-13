<?php

namespace MediaWiki\Extension\AbuseFilter;

use BagOStuff;

/**
 * Helper class for EmergencyWatcher. Wrapper around cache which tracks hits of recently
 * modified filters.
 */
class EmergencyCache {

	public const SERVICE_NAME = 'AbuseFilterEmergencyCache';

	/** @var BagOStuff */
	private $stash;

	/** @var int[] */
	private $ttlPerGroup;

	/**
	 * @param BagOStuff $stash
	 * @param int[] $ttlPerGroup
	 */
	public function __construct( BagOStuff $stash, array $ttlPerGroup ) {
		$this->stash = $stash;
		$this->ttlPerGroup = $ttlPerGroup;
	}

	/**
	 * Get recently modified filters in the group. Thanks to this, performance can be improved,
	 * because only a small subset of filters will need an update.
	 *
	 * @param string $group
	 * @return int[]
	 */
	public function getFiltersToCheckInGroup( string $group ): array {
		$filterToExpiry = $this->stash->get( $this->createGroupKey( $group ) );
		if ( $filterToExpiry === false ) {
			return [];
		}
		$time = (int)round( $this->stash->getCurrentTime() );
		return array_keys( array_filter(
			$filterToExpiry,
			static function ( $exp ) use ( $time ) {
				return $exp > $time;
			}
		) );
	}

	/**
	 * Create a new entry in cache for a filter and update the entry for the group.
	 * This method is usually called after the filter has been updated.
	 *
	 * @param int $filter
	 * @param string $group
	 * @return bool
	 */
	public function setNewForFilter( int $filter, string $group ): bool {
		$ttl = $this->ttlPerGroup[$group] ?? $this->ttlPerGroup['default'];
		$expiry = (int)round( $this->stash->getCurrentTime() + $ttl );
		$this->stash->merge(
			$this->createGroupKey( $group ),
			static function ( $cache, $key, $value ) use ( $filter, $expiry ) {
				if ( $value === false ) {
					$value = [];
				}
				// note that some filters may have already had their keys expired
				// we are currently filtering them out in getFiltersToCheckInGroup
				// but if necessary, it can be done here
				$value[$filter] = $expiry;
				return $value;
			},
			$expiry
		);
		return $this->stash->set(
			$this->createFilterKey( $filter ),
			[ 'total' => 0, 'matches' => 0, 'expiry' => $expiry ],
			$expiry
		);
	}

	/**
	 * Increase the filter's 'total' value by one and possibly also the 'matched' value.
	 *
	 * @param int $filter
	 * @param bool $matched Whether the filter matched the action
	 * @return bool
	 */
	public function incrementForFilter( int $filter, bool $matched ): bool {
		return $this->stash->merge(
			$this->createFilterKey( $filter ),
			static function ( $cache, $key, $value, &$expiry ) use ( $matched ) {
				if ( $value === false ) {
					return false;
				}
				$value['total']++;
				if ( $matched ) {
					$value['matches']++;
				}
				// enforce the prior TTL
				$expiry = $value['expiry'];
				return $value;
			}
		);
	}

	/**
	 * Get the cache entry for the filter. Returns false when the key has already expired.
	 * Otherwise it returns the entry formatted as [ 'total' => number of actions,
	 * 'matches' => number of hits ] (since the last filter modification).
	 *
	 * @param int $filter
	 * @return array|false
	 */
	public function getForFilter( int $filter ) {
		$value = $this->stash->get( $this->createFilterKey( $filter ) );
		if ( $value !== false ) {
			unset( $value['expiry'] );
		}
		return $value;
	}

	/**
	 * @param string $group
	 * @return string
	 */
	private function createGroupKey( string $group ): string {
		return $this->stash->makeKey( 'abusefilter', 'emergency', 'group', $group );
	}

	/**
	 * @param int $filter
	 * @return string
	 */
	private function createFilterKey( int $filter ): string {
		return $this->stash->makeKey( 'abusefilter', 'emergency', 'filter', $filter );
	}

}

<?php

namespace MediaWiki\Extension\Notifications\Cache;

use Iterator;
use MapCacheLRU;

/**
 * Base Local cache object, which borrows the concept from Flow user listener
 */
abstract class LocalCache {

	/**
	 * Max number of objects to hold in $targets.  In theory, 1000
	 * is very hard to reach in a normal web request. We need to
	 * put cap, so it doesn't reach memory limit when running email
	 * digest against large amount of notifications
	 */
	private const TARGET_MAX_NUM = 1000;

	/**
	 * Target object cache
	 * @var MapCacheLRU
	 */
	protected $targets;

	/**
	 * Lookup ids that have not been resolved for a target
	 * @var bool[]
	 */
	private $lookups = [];

	/**
	 * Resolve ids in lookups to targets
	 *
	 * @param int[] $lookups
	 * @return Iterator
	 */
	abstract protected function resolve( array $lookups );

	/**
	 * Instances should be obtained via EchoServices / MediaWikiServices.
	 *
	 * @private
	 */
	public function __construct() {
		$this->targets = new MapCacheLRU( self::TARGET_MAX_NUM );
	}

	/**
	 * Add a key to the lookup and the key is used to resolve cache target
	 *
	 * @param int $key
	 */
	public function add( $key ) {
		if (
			count( $this->lookups ) < self::TARGET_MAX_NUM
			&& !$this->targets->get( (string)$key )
		) {
			$this->lookups[$key] = true;
		}
	}

	/**
	 * Get the cache target based on the key
	 *
	 * @param int $key
	 * @return mixed|null
	 */
	public function get( $key ) {
		$target = $this->targets->get( (string)$key );
		if ( $target ) {
			return $target;
		}

		if ( isset( $this->lookups[ $key ] ) ) {
			// Resolve the lookup batch and store results in the cache
			$targets = $this->resolve( array_keys( $this->lookups ) );
			foreach ( $targets as $id => $val ) {
				$this->targets->set( $id, $val );
			}
			$this->lookups = [];
			$target = $this->targets->get( (string)$key );
			if ( $target ) {
				return $target;
			}
		}

		return null;
	}

	/**
	 * Clear everything in local cache
	 */
	public function clearAll() {
		$this->targets->clear();
		$this->lookups = [];
	}

}

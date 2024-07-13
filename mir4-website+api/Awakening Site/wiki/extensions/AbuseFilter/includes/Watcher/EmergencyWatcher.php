<?php

namespace MediaWiki\Extension\AbuseFilter\Watcher;

use InvalidArgumentException;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Deferred\AutoCommitUpdate;
use MediaWiki\Deferred\DeferredUpdates;
use MediaWiki\Extension\AbuseFilter\EchoNotifier;
use MediaWiki\Extension\AbuseFilter\EmergencyCache;
use MediaWiki\Extension\AbuseFilter\FilterLookup;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\LBFactory;

/**
 * Service for monitoring filters with restricted actions and preventing them
 * from executing destructive actions ("throttling")
 *
 * @todo We should log throttling somewhere
 */
class EmergencyWatcher implements Watcher {
	public const SERVICE_NAME = 'AbuseFilterEmergencyWatcher';

	public const CONSTRUCTOR_OPTIONS = [
		'AbuseFilterEmergencyDisableAge',
		'AbuseFilterEmergencyDisableCount',
		'AbuseFilterEmergencyDisableThreshold',
	];

	/** @var EmergencyCache */
	private $cache;

	/** @var LBFactory */
	private $lbFactory;

	/** @var FilterLookup */
	private $filterLookup;

	/** @var EchoNotifier */
	private $notifier;

	/** @var ServiceOptions */
	private $options;

	/**
	 * @param EmergencyCache $cache
	 * @param LBFactory $lbFactory
	 * @param FilterLookup $filterLookup
	 * @param EchoNotifier $notifier
	 * @param ServiceOptions $options
	 */
	public function __construct(
		EmergencyCache $cache,
		LBFactory $lbFactory,
		FilterLookup $filterLookup,
		EchoNotifier $notifier,
		ServiceOptions $options
	) {
		$options->assertRequiredOptions( self::CONSTRUCTOR_OPTIONS );
		$this->cache = $cache;
		$this->lbFactory = $lbFactory;
		$this->filterLookup = $filterLookup;
		$this->notifier = $notifier;
		$this->options = $options;
	}

	/**
	 * Determine which filters must be throttled, i.e. their potentially dangerous
	 *  actions must be disabled.
	 *
	 * @param int[] $filters The filters to check
	 * @param string $group Group the filters belong to
	 * @return int[] Array of filters to be throttled
	 */
	public function getFiltersToThrottle( array $filters, string $group ): array {
		$filters = array_intersect(
			$filters,
			$this->cache->getFiltersToCheckInGroup( $group )
		);
		if ( $filters === [] ) {
			return [];
		}

		$threshold = $this->getEmergencyValue( 'threshold', $group );
		$hitCountLimit = $this->getEmergencyValue( 'count', $group );
		$maxAge = $this->getEmergencyValue( 'age', $group );

		$time = (int)wfTimestamp( TS_UNIX );

		$throttleFilters = [];
		foreach ( $filters as $filter ) {
			$filterObj = $this->filterLookup->getFilter( $filter, false );
			// TODO: consider removing the filter from the group key
			// after throttling
			if ( $filterObj->isThrottled() ) {
				continue;
			}

			$filterAge = (int)wfTimestamp( TS_UNIX, $filterObj->getTimestamp() );
			$exemptTime = $filterAge + $maxAge;

			// Optimize for the common case when filters are well-established
			// This check somewhat duplicates the role of cache entry's TTL
			// and could as well be removed
			if ( $exemptTime <= $time ) {
				continue;
			}

			// TODO: this value might be stale, there is no guarantee the match
			// has actually been recorded now
			$cacheValue = $this->cache->getForFilter( $filter );
			if ( $cacheValue === false ) {
				continue;
			}

			[ 'total' => $totalActions, 'matches' => $matchCount ] = $cacheValue;

			if ( $matchCount > $hitCountLimit && ( $matchCount / $totalActions ) > $threshold ) {
				// More than AbuseFilterEmergencyDisableCount matches, constituting more than
				// AbuseFilterEmergencyDisableThreshold (a fraction) of last few edits.
				// Disable it.
				$throttleFilters[] = $filter;
			}
		}

		return $throttleFilters;
	}

	/**
	 * Determine which a filters must be throttled and apply the throttling
	 *
	 * @inheritDoc
	 */
	public function run( array $localFilters, array $globalFilters, string $group ): void {
		$throttleFilters = $this->getFiltersToThrottle( $localFilters, $group );
		if ( !$throttleFilters ) {
			return;
		}

		DeferredUpdates::addUpdate(
			new AutoCommitUpdate(
				$this->lbFactory->getPrimaryDatabase(),
				__METHOD__,
				static function ( IDatabase $dbw, $fname ) use ( $throttleFilters ) {
					$dbw->update(
						'abuse_filter',
						[ 'af_throttled' => 1 ],
						[ 'af_id' => $throttleFilters ],
						$fname
					);
				}
			)
		);
		DeferredUpdates::addCallableUpdate( function () use ( $throttleFilters ) {
			foreach ( $throttleFilters as $filter ) {
				$this->notifier->notifyForFilter( $filter );
			}
		} );
	}

	/**
	 * @param string $type The value to get, either "threshold", "count" or "age"
	 * @param string $group The filter's group (as defined in $wgAbuseFilterValidGroups)
	 * @return mixed
	 */
	private function getEmergencyValue( string $type, string $group ) {
		switch ( $type ) {
			case 'threshold':
				$opt = 'AbuseFilterEmergencyDisableThreshold';
				break;
			case 'count':
				$opt = 'AbuseFilterEmergencyDisableCount';
				break;
			case 'age':
				$opt = 'AbuseFilterEmergencyDisableAge';
				break;
			default:
				// @codeCoverageIgnoreStart
				throw new InvalidArgumentException( '$type must be either "threshold", "count" or "age"' );
				// @codeCoverageIgnoreEnd
		}

		$value = $this->options->get( $opt );
		return $value[$group] ?? $value['default'];
	}
}

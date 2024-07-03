<?php

namespace MediaWiki\Extension\AbuseFilter;

use BagOStuff;
use IBufferingStatsdDataFactory;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Title\Title;
use Psr\Log\LoggerInterface;
use Wikimedia\WRStats\LocalEntityKey;
use Wikimedia\WRStats\WRStatsFactory;

/**
 * This class is used to create, store, and retrieve profiling information for single filters and
 * groups of filters.
 *
 * @internal
 */
class FilterProfiler {
	public const SERVICE_NAME = 'AbuseFilterFilterProfiler';

	public const CONSTRUCTOR_OPTIONS = [
		'AbuseFilterConditionLimit',
		'AbuseFilterSlowFilterRuntimeLimit',
	];

	/**
	 * How long to keep profiling data in cache (in seconds)
	 */
	private const STATS_STORAGE_PERIOD = BagOStuff::TTL_DAY;

	/** The stats time bucket size */
	private const STATS_TIME_STEP = self::STATS_STORAGE_PERIOD / 12;

	/** The WRStats spec common to all metrics */
	private const STATS_TEMPLATE = [
		'sequences' => [ [
			'timeStep' => self::STATS_TIME_STEP,
			'expiry' => self::STATS_STORAGE_PERIOD,
		] ],
	];

	private const KEY_PREFIX = 'abusefilter-profile';

	/** @var WRStatsFactory */
	private $statsFactory;

	/** @var ServiceOptions */
	private $options;

	/** @var string */
	private $localWikiID;

	/** @var IBufferingStatsdDataFactory */
	private $statsd;

	/** @var LoggerInterface */
	private $logger;

	/** @var array */
	private $statsSpecs;

	/**
	 * @param WRStatsFactory $statsFactory
	 * @param ServiceOptions $options
	 * @param string $localWikiID
	 * @param IBufferingStatsdDataFactory $statsd
	 * @param LoggerInterface $logger
	 */
	public function __construct(
		WRStatsFactory $statsFactory,
		ServiceOptions $options,
		string $localWikiID,
		IBufferingStatsdDataFactory $statsd,
		LoggerInterface $logger
	) {
		$options->assertRequiredOptions( self::CONSTRUCTOR_OPTIONS );
		$this->statsFactory = $statsFactory;
		$this->options = $options;
		$this->localWikiID = $localWikiID;
		$this->statsd = $statsd;
		$this->logger = $logger;
		$this->statsSpecs = [
			'count' => self::STATS_TEMPLATE,
			'total' => self::STATS_TEMPLATE,
			'overflow' => self::STATS_TEMPLATE,
			'matches' => self::STATS_TEMPLATE,
			'total-time' => [ 'resolution' => 1e-3 ] + self::STATS_TEMPLATE,
			'total-cond' => self::STATS_TEMPLATE
		];
	}

	/**
	 * @param int $filter
	 */
	public function resetFilterProfile( int $filter ): void {
		$writer = $this->statsFactory->createWriter(
			$this->statsSpecs,
			self::KEY_PREFIX
		);
		$writer->resetAll( [ $this->filterProfileKey( $filter ) ] );
	}

	/**
	 * Retrieve per-filter statistics.
	 *
	 * @param int $filter
	 * @return array See self::NULL_FILTER_PROFILE for the returned array structure
	 * @phan-return array{count:int,matches:int,total-time:float,total-cond:int}
	 */
	public function getFilterProfile( int $filter ): array {
		$reader = $this->statsFactory->createReader(
			$this->statsSpecs,
			self::KEY_PREFIX
		);
		return $reader->total( $reader->getRates(
			[ 'count', 'matches', 'total-time', 'total-cond' ],
			$this->filterProfileKey( $filter ),
			$reader->latest( self::STATS_STORAGE_PERIOD )
		) );
	}

	/**
	 * Retrieve per-group statistics.
	 *
	 * @param string $group
	 * @return array See self::NULL_GROUP_PROFILE for the returned array structure
	 * @phan-return array{total:int,overflow:int,total-time:float,total-cond:int,matches:int}
	 */
	public function getGroupProfile( string $group ): array {
		$reader = $this->statsFactory->createReader(
			$this->statsSpecs,
			self::KEY_PREFIX
		);
		return $reader->total( $reader->getRates(
			[ 'total', 'overflow', 'total-time', 'total-cond', 'matches' ],
			$this->filterProfileGroupKey( $group ),
			$reader->latest( self::STATS_STORAGE_PERIOD )
		) );
	}

	/**
	 * Record per-filter profiling data
	 *
	 * @param int $filter
	 * @param float $time Time taken, in milliseconds
	 * @param int $conds
	 * @param bool $matched
	 */
	private function recordProfilingResult( int $filter, float $time, int $conds, bool $matched ): void {
		$key = $this->filterProfileKey( $filter );
		$writer = $this->statsFactory->createWriter(
			$this->statsSpecs,
			self::KEY_PREFIX
		);
		$writer->incr( 'count', $key );
		if ( $matched ) {
			$writer->incr( 'matches', $key );
		}
		$writer->incr( 'total-time', $key, $time );
		$writer->incr( 'total-cond', $key, $conds );
		$writer->flush();
	}

	/**
	 * Update global statistics
	 *
	 * @param string $group
	 * @param int $condsUsed The amount of used conditions
	 * @param float $totalTime Time taken, in milliseconds
	 * @param bool $anyMatch Whether at least one filter matched the action
	 */
	public function recordStats( string $group, int $condsUsed, float $totalTime, bool $anyMatch ): void {
		$writer = $this->statsFactory->createWriter(
			$this->statsSpecs,
			self::KEY_PREFIX
		);
		$key = $this->filterProfileGroupKey( $group );

		$writer->incr( 'total', $key );
		$writer->incr( 'total-time', $key, $totalTime );
		$writer->incr( 'total-cond', $key, $condsUsed );

		// Increment overflow counter, if our condition limit overflowed
		if ( $condsUsed > $this->options->get( 'AbuseFilterConditionLimit' ) ) {
			$writer->incr( 'overflow', $key );
		}

		// Increment counter by 1 if there was at least one match
		if ( $anyMatch ) {
			$writer->incr( 'matches', $key );
		}
		$writer->flush();
	}

	/**
	 * Record runtime profiling data for all filters together
	 *
	 * @param int $totalFilters
	 * @param int $totalConditions
	 * @param float $runtime
	 * @codeCoverageIgnore
	 */
	public function recordRuntimeProfilingResult( int $totalFilters, int $totalConditions, float $runtime ): void {
		$keyPrefix = 'abusefilter.runtime-profile.' . $this->localWikiID . '.';

		$this->statsd->timing( $keyPrefix . 'runtime', $runtime );
		$this->statsd->timing( $keyPrefix . 'total_filters', $totalFilters );
		$this->statsd->timing( $keyPrefix . 'total_conditions', $totalConditions );
	}

	/**
	 * Record per-filter profiling, for all filters
	 *
	 * @param Title $title
	 * @param array $data Profiling data
	 * @phan-param array<string,array{time:float,conds:int,result:bool}> $data
	 */
	public function recordPerFilterProfiling( Title $title, array $data ): void {
		$slowFilterThreshold = $this->options->get( 'AbuseFilterSlowFilterRuntimeLimit' );

		foreach ( $data as $filterName => $params ) {
			[ $filterID, $global ] = GlobalNameUtils::splitGlobalName( $filterName );
			// @todo Maybe add a parameter to recordProfilingResult to record global filters
			// data separately (in the foreign wiki)
			if ( !$global ) {
				$this->recordProfilingResult(
					$filterID,
					$params['time'],
					$params['conds'],
					$params['result']
				);
			}

			if ( $params['time'] > $slowFilterThreshold ) {
				$this->recordSlowFilter(
					$title,
					$filterName,
					$params['time'],
					$params['conds'],
					$params['result'],
					$global
				);
			}
		}
	}

	/**
	 * Logs slow filter's runtime data for later analysis
	 *
	 * @param Title $title
	 * @param string $filterId
	 * @param float $runtime
	 * @param int $totalConditions
	 * @param bool $matched
	 * @param bool $global
	 */
	private function recordSlowFilter(
		Title $title,
		string $filterId,
		float $runtime,
		int $totalConditions,
		bool $matched,
		bool $global
	): void {
		$this->logger->info(
			'Edit filter {filter_id} on {wiki} is taking longer than expected',
			[
				'wiki' => $this->localWikiID,
				'filter_id' => $filterId,
				'title' => $title->getPrefixedText(),
				'runtime' => $runtime,
				'matched' => $matched,
				'total_conditions' => $totalConditions,
				'global' => $global
			]
		);
	}

	/**
	 * Get the WRStats entity key used to store per-filter profiling data.
	 *
	 * @param int $filter
	 * @return LocalEntityKey
	 */
	private function filterProfileKey( int $filter ): LocalEntityKey {
		return new LocalEntityKey( [ 'filter', (string)$filter ] );
	}

	/**
	 * WRStats entity key used to store overall profiling data for rule groups
	 *
	 * @param string $group
	 * @return LocalEntityKey
	 */
	private function filterProfileGroupKey( string $group ): LocalEntityKey {
		return new LocalEntityKey( [ 'group', $group ] );
	}
}

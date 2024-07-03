<?php

namespace MediaWiki\Extension\AbuseFilter;

use BagOStuff;
use IBufferingStatsdDataFactory;
use InvalidArgumentException;
use MediaWiki\Extension\AbuseFilter\Variables\VariableHolder;
use MediaWiki\Extension\AbuseFilter\Variables\VariablesManager;
use MediaWiki\Linker\LinkTarget;
use Psr\Log\LoggerInterface;

/**
 * Wrapper around cache for storing and retrieving data from edit stash
 */
class EditStashCache {

	private const CACHE_VERSION = 'v5';

	/** @var BagOStuff */
	private $cache;

	/** @var IBufferingStatsdDataFactory */
	private $statsdDataFactory;

	/** @var VariablesManager */
	private $variablesManager;

	/** @var LoggerInterface */
	private $logger;

	/** @var LinkTarget */
	private $target;

	/** @var string */
	private $group;

	/**
	 * @param BagOStuff $cache
	 * @param IBufferingStatsdDataFactory $statsdDataFactory
	 * @param VariablesManager $variablesManager
	 * @param LoggerInterface $logger
	 * @param LinkTarget $target
	 * @param string $group
	 */
	public function __construct(
		BagOStuff $cache,
		IBufferingStatsdDataFactory $statsdDataFactory,
		VariablesManager $variablesManager,
		LoggerInterface $logger,
		LinkTarget $target,
		string $group
	) {
		$this->cache = $cache;
		$this->statsdDataFactory = $statsdDataFactory;
		$this->variablesManager = $variablesManager;
		$this->logger = $logger;
		$this->target = $target;
		$this->group = $group;
	}

	/**
	 * @param VariableHolder $vars For creating the key
	 * @param array $data Data to store
	 */
	public function store( VariableHolder $vars, array $data ): void {
		$key = $this->getStashKey( $vars );
		$this->cache->set( $key, $data, BagOStuff::TTL_MINUTE );
		$this->logCache( 'store', $key );
	}

	/**
	 * Search the cache to find data for a previous execution done for the current edit.
	 *
	 * @param VariableHolder $vars For creating the key
	 * @return false|array False on cache miss, the array with data otherwise
	 */
	public function seek( VariableHolder $vars ) {
		$key = $this->getStashKey( $vars );
		$value = $this->cache->get( $key );
		$status = $value !== false ? 'hit' : 'miss';
		$this->logCache( $status, $key );
		return $value;
	}

	/**
	 * Log cache operations related to stashed edits, i.e. store, hit and miss
	 *
	 * @param string $type Either 'store', 'hit' or 'miss'
	 * @param string $key The cache key used
	 * @throws InvalidArgumentException
	 */
	private function logCache( string $type, string $key ): void {
		if ( !in_array( $type, [ 'store', 'hit', 'miss' ] ) ) {
			// @codeCoverageIgnoreStart
			throw new InvalidArgumentException( '$type must be either "store", "hit" or "miss"' );
			// @codeCoverageIgnoreEnd
		}
		$this->logger->debug(
			__METHOD__ . ": cache {logtype} for '{target}' (key {key}).",
			[ 'logtype' => $type, 'target' => $this->target, 'key' => $key ]
		);
		$this->statsdDataFactory->increment( "abusefilter.check-stash.$type" );
	}

	/**
	 * Get the stash key for the current variables
	 *
	 * @param VariableHolder $vars
	 * @return string
	 */
	private function getStashKey( VariableHolder $vars ): string {
		$inputVars = $this->variablesManager->exportNonLazyVars( $vars );
		// Exclude noisy fields that have superficial changes
		$excludedVars = [
			'old_html' => true,
			'new_html' => true,
			'user_age' => true,
			'timestamp' => true,
			'page_age' => true,
			'moved_from_age' => true,
			'moved_to_age' => true
		];

		$inputVars = array_diff_key( $inputVars, $excludedVars );
		ksort( $inputVars );
		$hash = md5( serialize( $inputVars ) );

		return $this->cache->makeKey(
			'abusefilter',
			'check-stash',
			$this->group,
			$hash,
			self::CACHE_VERSION
		);
	}

}

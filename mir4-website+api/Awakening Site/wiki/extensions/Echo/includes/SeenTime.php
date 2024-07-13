<?php

namespace MediaWiki\Extension\Notifications;

use BagOStuff;
use CachedBagOStuff;
use MediaWiki\Deferred\DeferredUpdates;
use MediaWiki\MediaWikiServices;
use MediaWiki\User\CentralId\CentralIdLookup;
use MediaWiki\User\User;
use ObjectCache;
use UnexpectedValueException;

/**
 * A small wrapper around ObjectCache to manage
 * storing the last time a user has seen notifications
 */
class SeenTime {

	/**
	 * Allowed notification types
	 * @var string[]
	 */
	private static $allowedTypes = [ 'alert', 'message' ];

	/**
	 * @var User
	 */
	private $user;

	/**
	 * @param User $user A logged in user
	 */
	private function __construct( User $user ) {
		$this->user = $user;
	}

	/**
	 * @param User $user
	 * @return SeenTime
	 */
	public static function newFromUser( User $user ) {
		return new self( $user );
	}

	/**
	 * Hold onto a cache for our operations. Static so it can reuse the same
	 * in-process cache in different instances.
	 *
	 * @return BagOStuff
	 */
	private static function cache() {
		static $wrappedCache = null;

		// Use a configurable cache backend (T222851) and wrap it with CachedBagOStuff
		// for an in-process cache (T144534)
		if ( $wrappedCache === null ) {
			$cacheConfig = MediaWikiServices::getInstance()->getMainConfig()->get( 'EchoSeenTimeCacheType' );
			if ( $cacheConfig === null ) {
				// Hooks::initEchoExtension sets EchoSeenTimeCacheType to $wgMainStash if it's
				// null, so this can only happen if $wgMainStash is also null
				throw new UnexpectedValueException(
					'Either $wgEchoSeenTimeCacheType or $wgMainStash must be set'
				);
			}
			return new CachedBagOStuff( ObjectCache::getInstance( $cacheConfig ) );
		}

		return $wrappedCache;
	}

	/**
	 * @param string $type Type of seen time to get
	 * @param int $format Format to return time in, defaults to TS_MW
	 * @return string|false Timestamp in specified format, or false if no stored time
	 */
	public function getTime( $type = 'all', $format = TS_MW ) {
		$vals = [];
		if ( $type === 'all' ) {
			foreach ( self::$allowedTypes as $allowed ) {
				// Use TS_MW, then convert later, so max works properly for
				// all formats.
				$vals[] = $this->getTime( $allowed, TS_MW );
			}

			return wfTimestamp( $format, min( $vals ) );
		}

		if ( !$this->validateType( $type ) ) {
			return false;
		}

		$data = self::cache()->get( $this->getMemcKey( $type ) );

		if ( $data === false ) {
			$userOptionsLookup = MediaWikiServices::getInstance()->getUserOptionsLookup();
			// Check if the user still has it set in their preferences
			$data = $userOptionsLookup->getOption( $this->user, 'echo-seen-time', false );
		}

		if ( $data === false ) {
			// We can't remember their real seen time, so reset everything to
			// unseen.
			$data = wfTimestamp( TS_MW, 1 );
		}
		return wfTimestamp( $format, $data );
	}

	/**
	 * Sets the seen time
	 *
	 * @param string $time Time, in TS_MW format
	 * @param string $type Type of seen time to set
	 */
	public function setTime( $time, $type = 'all' ) {
		if ( $type === 'all' ) {
			foreach ( self::$allowedTypes as $allowed ) {
				$this->setTime( $time, $allowed );
			}
			return;
		}

		if ( !$this->validateType( $type ) ) {
			return;
		}

		// Write to the in-memory cache immediately, and defer writing to
		// the real cache
		$key = $this->getMemcKey( $type );
		$cache = self::cache();
		$cache->set( $key, $time, $cache::TTL_YEAR, BagOStuff::WRITE_CACHE_ONLY );
		DeferredUpdates::addCallableUpdate( static function () use ( $key, $time, $cache ) {
			$cache->set( $key, $time, $cache::TTL_YEAR );
		} );
	}

	/**
	 * Validate the given type, make sure it is allowed.
	 *
	 * @param string $type Given type
	 * @return bool Type is allowed
	 */
	private function validateType( $type ) {
		return in_array( $type, self::$allowedTypes );
	}

	/**
	 * Build a memcached key.
	 *
	 * @param string $type Given notification type
	 * @return string Memcached key
	 */
	protected function getMemcKey( $type = 'all' ) {
		$localKey = self::cache()->makeKey(
			'echo', 'seen', $type, 'time', $this->user->getId()
		);
		$userOptionsLookup = MediaWikiServices::getInstance()->getUserOptionsLookup();

		if ( !$userOptionsLookup->getOption( $this->user, 'echo-cross-wiki-notifications' ) ) {
			return $localKey;
		}

		$globalId = MediaWikiServices::getInstance()
			->getCentralIdLookup()
			->centralIdFromLocalUser( $this->user, CentralIdLookup::AUDIENCE_RAW );

		if ( !$globalId ) {
			return $localKey;
		}

		return self::cache()->makeGlobalKey(
			'echo', 'seen', $type, 'time', $globalId
		);
	}
}

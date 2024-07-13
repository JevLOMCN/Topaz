<?php

namespace MediaWiki\Extension\Notifications;

use BadMethodCallException;
use MediaWiki\MediaWikiServices;
use MediaWiki\Preferences\MultiUsernameFilter;
use MediaWiki\User\User;
use WANObjectCache;

/**
 * Utilizes ContainmentList interface to provide a fluent interface to whitelist/blacklist
 * from multiple sources like global variables, wiki pages, etc.
 *
 * Initialize:
 *   $cache = ObjectCache::getLocalClusterInstance();
 *   $set = new ContainmentSet;
 *   $set->addArray( $wgSomeGlobalParameter );
 *   $set->addOnWiki( NS_USER, 'Foo/bar-baz', $cache, 'some_user_specific_cache_key' );
 *
 * Usage:
 *   if ( $set->contains( 'SomeUser' ) ) {
 *       ...
 *   }
 */
class ContainmentSet {
	/**
	 * @var ContainmentList[]
	 */
	protected $lists = [];

	/**
	 * @var User
	 */
	protected $recipient;

	/**
	 * @param User $recipient
	 */
	public function __construct( User $recipient ) {
		$this->recipient = $recipient;
	}

	/**
	 * Add an ContainmentList to the set of lists checked by self::contains()
	 *
	 * @param ContainmentList $list
	 */
	public function add( ContainmentList $list ) {
		$this->lists[] = $list;
	}

	/**
	 * Add a php array to the set of lists checked by self::contains()
	 *
	 * @param array $list
	 */
	public function addArray( array $list ) {
		$this->add( new ArrayList( $list ) );
	}

	/**
	 * Add a list from a user preference to the set of lists checked by self::contains().
	 *
	 * @param string $preferenceName
	 */
	public function addFromUserOption( string $preferenceName ) {
		$userOptionsLookup = MediaWikiServices::getInstance()->getUserOptionsLookup();
		$preference = $userOptionsLookup->getOption( $this->recipient, $preferenceName, [] );
		if ( $preference ) {
			$ids = MultiUsernameFilter::splitIds( $preference );
			$names = MediaWikiServices::getInstance()
				->getCentralIdLookup()
				->namesFromCentralIds( $ids, $this->recipient );
			$this->addArray( $names );
		}
	}

	/**
	 * Add a list of title IDs from a user preference to the set of lists
	 * checked by self::contains().
	 *
	 * @param string $preferenceName
	 */
	public function addTitleIDsFromUserOption( string $preferenceName ): void {
		$userOptionsLookup = MediaWikiServices::getInstance()->getUserOptionsLookup();
		$preference = $userOptionsLookup->getOption( $this->recipient, $preferenceName, [] );
		if ( !is_string( $preference ) ) {
			// We expect the preference data to be saved as a string via the
			// preferences form; if the user modified their data so it's no
			// longer a string, ignore it.
			return;
		}
		$titleIDs = preg_split( '/\n/', $preference, -1, PREG_SPLIT_NO_EMPTY );
		$this->addArray( $titleIDs );
	}

	/**
	 * Add a list from a wiki page to the set of lists checked by self::contains().  Data
	 * from wiki pages is cached via the BagOStuff.  Caching is disabled when passing a null
	 * $cache object.
	 *
	 * @param int $namespace An NS_* constant representing the mediawiki namespace of the page containing the list.
	 * @param string $title The title of the page containing the list.
	 * @param WANObjectCache|null $cache An object to cache the page with or null for no cache.
	 * @param string $cacheKeyPrefix A prefix to be combined with the pages latest revision id and used as a cache key.
	 */
	public function addOnWiki(
		$namespace, $title, WANObjectCache $cache = null, $cacheKeyPrefix = ''
	) {
		$list = new OnWikiList( $namespace, $title );
		if ( $cache ) {
			if ( $cacheKeyPrefix === '' ) {
				throw new BadMethodCallException( 'Cache requires providing a cache key prefix.' );
			}
			$list = new CachedList( $cache, $cacheKeyPrefix, $list );
		}
		$this->add( $list );
	}

	/**
	 * Test the wrapped lists for existence of $value
	 *
	 * @param mixed $value The value to look for
	 * @return bool True when the set contains the provided value
	 */
	public function contains( $value ) {
		foreach ( $this->lists as $list ) {
			// Use strict comparison to prevent the number 0 from matching all strings (T177825)
			if ( in_array( $value, $list->getValues(), true ) ) {
				return true;
			}
		}

		return false;
	}
}

<?php

namespace MediaWiki\Extension\SpamBlacklist;

use InvalidArgumentException;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use ObjectCache;
use TextContent;

/**
 * Base class for different kinds of blacklists
 */
abstract class BaseBlacklist {
	/**
	 * Array of blacklist sources
	 *
	 * @var string[]
	 */
	public $files = [];

	/**
	 * Array containing regexes to test against
	 *
	 * @var string[]|false
	 */
	protected $regexes = false;

	/**
	 * Chance of receiving a warning when the filter is hit
	 *
	 * @var int
	 */
	public $warningChance = 100;

	/**
	 * @var int
	 */
	public $warningTime = 600;

	/**
	 * @var int
	 */
	public $expiryTime = 900;

	/**
	 * Array containing blacklists that extend BaseBlacklist
	 *
	 * @var string[]
	 */
	private static $blacklistTypes = [
		'spam' => SpamBlacklist::class,
		'email' => EmailBlacklist::class,
	];

	/**
	 * Array of blacklist instances
	 *
	 * @var self[]
	 */
	private static $instances = [];

	/**
	 * @param array $settings
	 */
	public function __construct( $settings = [] ) {
		foreach ( $settings as $name => $value ) {
			$this->$name = $value;
		}
	}

	/**
	 * @param array $links
	 * @param ?Title $title
	 * @param User $user
	 * @param bool $preventLog
	 * @return mixed
	 */
	abstract public function filter(
		array $links,
		?Title $title,
		User $user,
		$preventLog = false
	);

	/**
	 * Adds a blacklist class to the registry
	 *
	 * @param string $type
	 * @param string $class
	 */
	public static function addBlacklistType( $type, $class ) {
		self::$blacklistTypes[$type] = $class;
	}

	/**
	 * Return the array of blacklist types currently defined
	 *
	 * @return string[]
	 */
	public static function getBlacklistTypes() {
		return self::$blacklistTypes;
	}

	/**
	 * @return SpamBlacklist
	 */
	public static function getSpamBlacklist() {
		// @phan-suppress-next-line PhanTypeMismatchReturnSuperType
		return self::getInstance( 'spam' );
	}

	/**
	 * @return EmailBlacklist
	 */
	public static function getEmailBlacklist() {
		// @phan-suppress-next-line PhanTypeMismatchReturnSuperType
		return self::getInstance( 'email' );
	}

	/**
	 * Returns an instance of the given blacklist
	 *
	 * @deprecated Use getSpamBlacklist() or getEmailBlacklist() instead
	 * @param string $type Code for the blacklist
	 * @return BaseBlacklist
	 */
	public static function getInstance( $type ) {
		if ( !isset( self::$blacklistTypes[$type] ) ) {
			throw new InvalidArgumentException( "Invalid blacklist type '$type' passed to " . __METHOD__ );
		}

		if ( !isset( self::$instances[$type] ) ) {
			global $wgBlacklistSettings;

			// Prevent notices
			if ( !isset( $wgBlacklistSettings[$type] ) ) {
				$wgBlacklistSettings[$type] = [];
			}

			$class = self::$blacklistTypes[$type];
			self::$instances[$type] = new $class( $wgBlacklistSettings[$type] );
		}

		return self::$instances[$type];
	}

	/**
	 * Clear instance cache. For use during testing.
	 */
	public static function clearInstanceCache() {
		self::$instances = [];
	}

	/**
	 * Returns the code for the blacklist implementation
	 *
	 * @return string
	 */
	abstract protected function getBlacklistType();

	/**
	 * Check if the given local page title is a spam regex source.
	 *
	 * @param Title $title
	 * @return bool
	 */
	public static function isLocalSource( Title $title ) {
		global $wgDBname, $wgBlacklistSettings;

		if ( $title->inNamespace( NS_MEDIAWIKI ) ) {
			$sources = [];
			foreach ( self::$blacklistTypes as $type => $class ) {
				// For the built in types, this results in the use of:
				// spam-blacklist, spam-whitelist
				// email-blacklist, email-whitelist
				$type = ucfirst( $type );
				$sources[] = "$type-blacklist";
				$sources[] = "$type-whitelist";
			}

			if ( in_array( $title->getDBkey(), $sources ) ) {
				return true;
			}
		}

		$thisHttp = wfExpandUrl( $title->getFullUrl( 'action=raw' ), PROTO_HTTP );
		$thisHttpRegex = '/^' . preg_quote( $thisHttp, '/' ) . '(?:&.*)?$/';

		$files = [];
		foreach ( self::$blacklistTypes as $type => $class ) {
			if ( isset( $wgBlacklistSettings[$type]['files'] ) ) {
				$files += $wgBlacklistSettings[$type]['files'];
			}
		}

		foreach ( $files as $fileName ) {
			$matches = [];
			if ( preg_match( '/^DB: (\w*) (.*)$/', $fileName, $matches ) ) {
				if ( $wgDBname === $matches[1] && $matches[2] === $title->getPrefixedDbKey() ) {
					// Local DB fetch of this page...
					return true;
				}
			} elseif ( preg_match( $thisHttpRegex, $fileName ) ) {
				// Raw view of this page
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns the type of blacklist from the given title
	 *
	 * @todo building a regex for this is pretty overkill
	 * @param Title $title
	 * @return bool|string
	 */
	public static function getTypeFromTitle( Title $title ) {
		$contLang = MediaWikiServices::getInstance()->getContentLanguage();

		$types = array_map( [ $contLang, 'ucfirst' ], array_keys( self::$blacklistTypes ) );
		$regex = '/(' . implode( '|', $types ) . ')-(?:blacklist|whitelist)/';

		if ( preg_match( $regex, $title->getDBkey(), $m ) ) {
			return strtolower( $m[1] );
		}

		return false;
	}

	/**
	 * Fetch local and (possibly cached) remote blacklists.
	 * Will be cached locally across multiple invocations.
	 * @return string[] set of regular expressions, potentially empty.
	 */
	public function getBlacklists() {
		if ( $this->regexes === false ) {
			$this->regexes = array_merge(
				$this->getLocalBlacklists(),
				$this->getSharedBlacklists()
			);
		}
		return $this->regexes;
	}

	/**
	 * Returns the local blacklist
	 *
	 * @return string[] Regular expressions
	 */
	public function getLocalBlacklists() {
		$type = $this->getBlacklistType();
		$cache = MediaWikiServices::getInstance()->getMainWANObjectCache();

		return $cache->getWithSetCallback(
			$cache->makeKey( 'spamblacklist', $type, 'blacklist-regex' ),
			$this->expiryTime,
			function () use ( $type ) {
				return SpamRegexBatch::regexesFromMessage( "{$type}-blacklist", $this );
			}
		);
	}

	/**
	 * Returns the (local) whitelist
	 *
	 * @return string[] Regular expressions
	 */
	public function getWhitelists() {
		$type = $this->getBlacklistType();
		$cache = MediaWikiServices::getInstance()->getMainWANObjectCache();

		return $cache->getWithSetCallback(
			$cache->makeKey( 'spamblacklist', $type, 'whitelist-regex' ),
			$this->expiryTime,
			function () use ( $type ) {
				return SpamRegexBatch::regexesFromMessage( "{$type}-whitelist", $this );
			}
		);
	}

	/**
	 * Fetch (possibly cached) remote blacklists.
	 * @return array
	 */
	private function getSharedBlacklists() {
		$listType = $this->getBlacklistType();

		wfDebugLog( 'SpamBlacklist', "Loading $listType regex..." );

		if ( !$this->files ) {
			# No lists
			wfDebugLog( 'SpamBlacklist', "no files specified\n" );
			return [];
		}

		if ( defined( 'MW_PHPUNIT_TEST' ) ) {
			wfDebugLog( 'SpamBlacklist', 'remote loading disabled during PHPUnit test' );
			return [];
		}

		$miss = false;
		$cache = MediaWikiServices::getInstance()->getMainWANObjectCache();
		$regexes = $cache->getWithSetCallback(
			// This used to be cached per-site, but that could be bad on a shared
			// server where not all wikis have the same configuration.
			$cache->makeKey( 'spamblacklist', $listType, 'shared-blacklist-regex' ),
			$this->expiryTime,
			function () use ( &$miss ) {
				$miss = true;
				return $this->buildSharedBlacklists();
			}
		);

		if ( !$miss ) {
			wfDebugLog( 'SpamBlacklist', "Got shared spam regexes from cache\n" );
		}

		return $regexes;
	}

	/**
	 * Clear all primary blacklist cache keys
	 */
	public function clearCache() {
		$listType = $this->getBlacklistType();

		$cache = MediaWikiServices::getInstance()->getMainWANObjectCache();
		$cache->delete( $cache->makeKey( 'spamblacklist', $listType, 'shared-blacklist-regex' ) );
		$cache->delete( $cache->makeKey( 'spamblacklist', $listType, 'blacklist-regex' ) );
		$cache->delete( $cache->makeKey( 'spamblacklist', $listType, 'whitelist-regex' ) );

		wfDebugLog( 'SpamBlacklist', "$listType blacklist local cache cleared.\n" );
	}

	private function buildSharedBlacklists() {
		$regexes = [];
		$listType = $this->getBlacklistType();
		# Load lists
		wfDebugLog( 'SpamBlacklist', "Constructing $listType blacklist\n" );
		foreach ( $this->files as $fileName ) {
			$matches = [];
			if ( preg_match( '/^DB: ([\w-]*) (.*)$/', $fileName, $matches ) ) {
				$text = $this->getArticleText( $matches[1], $matches[2] );
			} elseif ( preg_match( '/^(https?:)?\/\//', $fileName ) ) {
				$text = $this->getHttpText( $fileName );
			} else {
				$text = file_get_contents( $fileName );
				wfDebugLog( 'SpamBlacklist', "got from file $fileName\n" );
			}

			if ( $text ) {
				// Build a separate batch of regexes from each source.
				// While in theory we could squeeze a little efficiency
				// out of combining multiple sources in one regex, if
				// there's a bad line in one of them we'll gain more
				// from only having to break that set into smaller pieces.
				$regexes = array_merge(
					$regexes,
					SpamRegexBatch::regexesFromText( $text, $this, $fileName )
				);
			}
		}

		return $regexes;
	}

	private function getHttpText( $fileName ) {
		global $wgMessageCacheType;
		// FIXME: This is a hack to use Memcached where possible (incl. WMF),
		// but have CACHE_DB as fallback (instead of no cache).
		// This might be a good candidate for T248005.
		$cache = ObjectCache::getInstance( $wgMessageCacheType );

		$listType = $this->getBlacklistType();
		// There are two keys, when the warning key expires, a random thread will refresh
		// the real key. This reduces the chance of multiple requests under high traffic
		// conditions.
		$key = $cache->makeGlobalKey( "blacklist_file_{$listType}", $fileName );
		$warningKey = $cache->makeKey( "filewarning_{$listType}", $fileName );
		$httpText = $cache->get( $key );
		$warning = $cache->get( $warningKey );

		if ( !is_string( $httpText ) || ( !$warning && !mt_rand( 0, $this->warningChance ) ) ) {
			wfDebugLog( 'SpamBlacklist', "Loading $listType blacklist from $fileName\n" );
			$httpText = MediaWikiServices::getInstance()->getHttpRequestFactory()
				->get( $fileName, [], __METHOD__ );
			if ( $httpText === false ) {
				wfDebugLog( 'SpamBlacklist', "Error loading $listType blacklist from $fileName\n" );
			}
			$cache->set( $warningKey, 1, $this->warningTime );
			$cache->set( $key, $httpText, $this->expiryTime );
		} else {
			wfDebugLog( 'SpamBlacklist', "Got $listType blacklist from HTTP cache for $fileName\n" );
		}
		return $httpText;
	}

	/**
	 * Fetch an article from this or another local MediaWiki database.
	 *
	 * @param string $wiki
	 * @param string $pagename
	 * @return bool|string|null
	 */
	private function getArticleText( $wiki, $pagename ) {
		wfDebugLog( 'SpamBlacklist',
			"Fetching {$this->getBlacklistType()} blacklist from '$pagename' on '$wiki'...\n" );

		$services = MediaWikiServices::getInstance();

		// XXX: We do not know about custom namespaces on the target wiki here!
		$title = $services->getTitleParser()->parseTitle( $pagename );
		$store = $services->getRevisionStoreFactory()->getRevisionStore( $wiki );
		$rev = $store->getRevisionByTitle( $title );

		$content = $rev ? $rev->getContent( SlotRecord::MAIN ) : null;

		if ( !( $content instanceof TextContent ) ) {
			return false;
		}

		return $content->getText();
	}

	/**
	 * Returns the start of the regex for matches
	 *
	 * @return string
	 */
	public function getRegexStart() {
		return '/[a-z0-9_\-.]*';
	}

	/**
	 * Returns the end of the regex for matches
	 *
	 * @param int $batchSize
	 * @return string
	 */
	public function getRegexEnd( $batchSize ) {
		return ( $batchSize > 0 ) ? '/Sim' : '/im';
	}

	/**
	 * @param Title $title
	 * @param string[] $entries
	 * @param User $user
	 */
	public function warmCachesForFilter( Title $title, array $entries, User $user ) {
		// subclass this
	}
}

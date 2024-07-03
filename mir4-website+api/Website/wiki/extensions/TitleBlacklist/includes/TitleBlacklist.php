<?php
/**
 * Title Blacklist class
 * @author Victor Vasiliev
 * @copyright © 2007-2010 Victor Vasiliev et al
 * @license GPL-2.0-or-later
 * @file
 */

namespace MediaWiki\Extension\TitleBlacklist;

use BadMethodCallException;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use ObjectCache;
use TextContent;
use Wikimedia\AtEase\AtEase;

/**
 * @ingroup Extensions
 */

/**
 * Implements a title blacklist for MediaWiki
 */
class TitleBlacklist {
	/** @var TitleBlacklistEntry[]|null */
	private $mBlacklist = null;

	/** @var TitleBlacklistEntry[]|null */
	private $mWhitelist = null;

	/** @var TitleBlacklist|null */
	protected static $instance = null;

	/** Blacklist format */
	public const VERSION = 4;

	/**
	 * Get an instance of this class
	 *
	 * @return TitleBlacklist
	 */
	public static function singleton() {
		if ( self::$instance === null ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	/**
	 * Destroy/reset the current singleton instance.
	 *
	 * This is solely for testing and will fail unless MW_PHPUNIT_TEST is
	 * defined.
	 */
	public static function destroySingleton() {
		if ( !defined( 'MW_PHPUNIT_TEST' ) ) {
			throw new BadMethodCallException(
				'Can not invoke ' . __METHOD__ . '() ' .
				'out of tests (MW_PHPUNIT_TEST not set).'
			);
		}

		self::$instance = null;
	}

	/**
	 * Load all configured blacklist sources
	 */
	public function load() {
		global $wgTitleBlacklistSources, $wgTitleBlacklistCaching;

		$cache = MediaWikiServices::getInstance()->getMainWANObjectCache();
		// Try to find something in the cache
		$cachedBlacklist = $cache->get( $cache->makeKey( 'title_blacklist_entries' ) );
		if ( is_array( $cachedBlacklist ) && count( $cachedBlacklist ) > 0
			&& ( $cachedBlacklist[0]->getFormatVersion() == self::VERSION )
		) {
			$this->mBlacklist = $cachedBlacklist;
			return;
		}

		$sources = $wgTitleBlacklistSources;
		$sources['local'] = [ 'type' => 'message' ];
		$this->mBlacklist = [];
		foreach ( $sources as $sourceName => $source ) {
			$this->mBlacklist = array_merge(
				$this->mBlacklist,
				self::parseBlacklist( self::getBlacklistText( $source ), $sourceName )
			);
		}
		$cache->set( $cache->makeKey( 'title_blacklist_entries' ),
			$this->mBlacklist, $wgTitleBlacklistCaching['expiry'] );
		wfDebugLog( 'TitleBlacklist-cache', 'Updated ' . $cache->makeKey( 'title_blacklist_entries' )
			. ' with ' . count( $this->mBlacklist ) . ' entries.' );
	}

	/**
	 * Load local whitelist
	 */
	public function loadWhitelist() {
		global $wgTitleBlacklistCaching;

		$cache = MediaWikiServices::getInstance()->getMainWANObjectCache();
		$cachedWhitelist = $cache->get( $cache->makeKey( 'title_whitelist_entries' ) );
		if ( is_array( $cachedWhitelist ) && count( $cachedWhitelist ) > 0
			&& ( $cachedWhitelist[0]->getFormatVersion() != self::VERSION )
		) {
			$this->mWhitelist = $cachedWhitelist;
			return;
		}
		$this->mWhitelist = self::parseBlacklist( wfMessage( 'titlewhitelist' )
				->inContentLanguage()->text(), 'whitelist' );
		$cache->set( $cache->makeKey( 'title_whitelist_entries' ),
			$this->mWhitelist, $wgTitleBlacklistCaching['expiry'] );
	}

	/**
	 * Get the text of a blacklist from a specified source
	 *
	 * @param array $source A blacklist source from $wgTitleBlacklistSources
	 * @return string The content of the blacklist source as a string
	 */
	private static function getBlacklistText( $source ) {
		if ( !is_array( $source ) || count( $source ) <= 0 ) {
			// Return empty string in error case
			return '';
		}

		if ( $source['type'] == 'message' ) {
			return wfMessage( 'titleblacklist' )->inContentLanguage()->text();
		} elseif ( $source['type'] == 'localpage' && count( $source ) >= 2 ) {
			$title = Title::newFromText( $source['src'] );
			if ( $title === null ) {
				return '';
			}
			if ( $title->getNamespace() == NS_MEDIAWIKI ) {
				$msg = wfMessage( $title->getText() )->inContentLanguage();
				return $msg->isDisabled() ? '' : $msg->text();
			} else {
				$page = MediaWikiServices::getInstance()->getWikiPageFactory()->newFromTitle( $title );
				if ( $page->exists() ) {
					$content = $page->getContent();
					return ( $content instanceof TextContent ) ? $content->getText() : "";
				}
			}
		} elseif ( $source['type'] == 'url' && count( $source ) >= 2 ) {
			return self::getHttp( $source['src'] );
		} elseif ( $source['type'] == 'file' && count( $source ) >= 2 ) {
			if ( !file_exists( $source['src'] ) ) {
				return '';
			}
			return file_get_contents( $source['src'] );
		}

		return '';
	}

	/**
	 * Parse blacklist from a string
	 *
	 * @param string $list Text of a blacklist source
	 * @param string $sourceName
	 * @return TitleBlacklistEntry[]
	 */
	public static function parseBlacklist( $list, $sourceName ) {
		$lines = preg_split( "/\r?\n/", $list );
		$result = [];
		foreach ( $lines as $line ) {
			$entry = TitleBlacklistEntry::newFromString( $line, $sourceName );
			if ( $entry ) {
				$result[] = $entry;
			}
		}

		return $result;
	}

	/**
	 * Check whether the blacklist restricts given user
	 * performing a specific action on the given Title
	 *
	 * @param Title $title Title to check
	 * @param User $user User to check
	 * @param string $action Action to check; 'edit' if unspecified
	 * @param bool $override If set to true, overrides work
	 * @return TitleBlacklistEntry|bool The corresponding TitleBlacklistEntry if
	 * blacklisted; otherwise false
	 */
	public function userCannot( $title, $user, $action = 'edit', $override = true ) {
		$entry = $this->isBlacklisted( $title, $action );
		if ( !$entry ) {
			return false;
		}
		$params = $entry->getParams();
		if ( isset( $params['autoconfirmed'] ) && $user->isAllowed( 'autoconfirmed' ) ) {
			return false;
		}
		if ( $override && self::userCanOverride( $user, $action ) ) {
			return false;
		}
		return $entry;
	}

	/**
	 * Check whether the blacklist restricts
	 * performing a specific action on the given Title
	 *
	 * @param Title $title Title to check
	 * @param string $action Action to check; 'edit' if unspecified
	 * @return TitleBlacklistEntry|bool The corresponding TitleBlacklistEntry if blacklisted;
	 *         otherwise FALSE
	 */
	public function isBlacklisted( $title, $action = 'edit' ) {
		if ( !( $title instanceof Title ) ) {
			$title = Title::newFromText( $title );
			if ( !( $title instanceof Title ) ) {
				// The fact that the page name is invalid will stop whatever
				// action is going through. No sense in doing more work here.
				return false;
			}
		}
		$blacklist = $this->getBlacklist();
		$autoconfirmedItem = false;
		foreach ( $blacklist as $item ) {
			if ( $item->matches( $title->getFullText(), $action ) ) {
				if ( $this->isWhitelisted( $title, $action ) ) {
					return false;
				}
				$params = $item->getParams();
				if ( !isset( $params['autoconfirmed'] ) ) {
					return $item;
				}
				if ( !$autoconfirmedItem ) {
					$autoconfirmedItem = $item;
				}
			}
		}
		return $autoconfirmedItem;
	}

	/**
	 * Check whether it has been explicitly whitelisted that the
	 * current User may perform a specific action on the given Title
	 *
	 * @param Title $title Title to check
	 * @param string $action Action to check; 'edit' if unspecified
	 * @return bool True if whitelisted; otherwise false
	 */
	public function isWhitelisted( $title, $action = 'edit' ) {
		if ( !( $title instanceof Title ) ) {
			$title = Title::newFromText( $title );
		}
		$whitelist = $this->getWhitelist();
		foreach ( $whitelist as $item ) {
			if ( $item->matches( $title->getFullText(), $action ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Get the current blacklist
	 *
	 * @return TitleBlacklistEntry[]
	 */
	public function getBlacklist() {
		if ( $this->mBlacklist === null ) {
			$this->load();
		}
		return $this->mBlacklist;
	}

	/**
	 * Get the current whitelist
	 *
	 * @return TitleBlacklistEntry[]
	 */
	public function getWhitelist() {
		if ( $this->mWhitelist === null ) {
			$this->loadWhitelist();
		}
		return $this->mWhitelist;
	}

	/**
	 * Get the text of a blacklist source via HTTP
	 *
	 * @param string $url URL of the blacklist source
	 * @return string The content of the blacklist source as a string
	 */
	private static function getHttp( $url ) {
		global $wgTitleBlacklistCaching, $wgMessageCacheType;
		// FIXME: This is a hack to use Memcached where possible (incl. WMF),
		// but have CACHE_DB as fallback (instead of no cache).
		// This might be a good candidate for T248005.
		$cache = ObjectCache::getInstance( $wgMessageCacheType );

		// Globally shared
		$key = $cache->makeGlobalKey( 'title_blacklist_source', md5( $url ) );
		// Per-wiki
		$warnkey = $cache->makeKey( 'titleblacklistwarning', md5( $url ) );

		$result = $cache->get( $key );
		$warn = $cache->get( $warnkey );

		if ( !is_string( $result )
			|| ( !$warn && !mt_rand( 0, $wgTitleBlacklistCaching['warningchance'] ) )
		) {
			$result = MediaWikiServices::getInstance()->getHttpRequestFactory()
				->get( $url, [], __METHOD__ );
			$cache->set( $warnkey, 1, $wgTitleBlacklistCaching['warningexpiry'] );
			$cache->set( $key, $result, $wgTitleBlacklistCaching['expiry'] );
			if ( !$result ) {
				wfDebugLog( 'TitleBlacklist-cache', "Error loading title blacklist from $url\n" );
				$result = '';
			}
		}

		return $result;
	}

	/**
	 * Invalidate the blacklist cache
	 */
	public function invalidate() {
		$cache = MediaWikiServices::getInstance()->getMainWANObjectCache();
		$cache->delete( $cache->makeKey( 'title_blacklist_entries' ) );
	}

	/**
	 * Validate a new blacklist
	 *
	 * @suppress PhanParamSuspiciousOrder The preg_match() params are in the correct order
	 * @param TitleBlacklistEntry[] $blacklist
	 * @return string[] List of invalid entries; empty array means blacklist is valid
	 */
	public function validate( array $blacklist ) {
		$badEntries = [];
		foreach ( $blacklist as $e ) {
			AtEase::suppressWarnings();
			$regex = $e->getRegex();
			// @phan-suppress-next-line SecurityCheck-ReDoS
			if ( preg_match( "/{$regex}/u", '' ) === false ) {
				$badEntries[] = $e->getRaw();
			}
			AtEase::restoreWarnings();
		}
		return $badEntries;
	}

	/**
	 * Indicates whether user can override blacklist on certain action.
	 *
	 * @param User $user
	 * @param string $action
	 *
	 * @return bool
	 */
	public static function userCanOverride( $user, $action ) {
		return $user->isAllowed( 'tboverride' ) ||
			( $action == 'new-account' && $user->isAllowed( 'tboverride-account' ) );
	}
}

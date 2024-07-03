<?php

namespace MediaWiki\Extension\Scribunto\Engines\LuaCommon;

use MediaWiki\Category\Category;
use MediaWiki\MainConfigNames;
use MediaWiki\MediaWikiServices;
use MediaWiki\SiteStats\SiteStats;
use MediaWiki\Specials\SpecialVersion;
use MediaWiki\Title\Title;

class SiteLibrary extends LibraryBase {
	/** @var string|null */
	private static $namespacesCacheLang = null;
	/** @var array[]|null */
	private static $namespacesCache = null;
	/** @var array[] */
	private static $interwikiMapCache = [];
	/** @var int[][] */
	private $pagesInCategoryCache = [];

	public function register() {
		$lib = [
			'getNsIndex' => [ $this, 'getNsIndex' ],
			'pagesInCategory' => [ $this, 'pagesInCategory' ],
			'pagesInNamespace' => [ $this, 'pagesInNamespace' ],
			'usersInGroup' => [ $this, 'usersInGroup' ],
			'interwikiMap' => [ $this, 'interwikiMap' ],
		];
		$parser = $this->getParser();
		$services = MediaWikiServices::getInstance();
		$config = $services->getMainConfig();
		$contLang = $services->getContentLanguage();
		$info = [
			'siteName' => $config->get( MainConfigNames::Sitename ),
			'server' => $config->get( MainConfigNames::Server ),
			'scriptPath' => $config->get( MainConfigNames::ScriptPath ),
			'stylePath' => $config->get( MainConfigNames::StylePath ),
			'currentVersion' => SpecialVersion::getVersion(
				'', $parser ? $parser->getTargetLanguage() : $contLang
			),
		];

		if ( !self::$namespacesCache || self::$namespacesCacheLang !== $contLang->getCode() ) {
			$namespaces = [];
			$namespacesByName = [];
			$namespaceInfo = $services->getNamespaceInfo();
			foreach ( $contLang->getFormattedNamespaces() as $ns => $title ) {
				$canonical = $namespaceInfo->getCanonicalName( $ns );
				$namespaces[$ns] = [
					'id' => $ns,
					'name' => $title,
					'canonicalName' => strtr( $canonical, '_', ' ' ),
					'hasSubpages' => $namespaceInfo->hasSubpages( $ns ),
					'hasGenderDistinction' => $namespaceInfo->hasGenderDistinction( $ns ),
					'isCapitalized' => $namespaceInfo->isCapitalized( $ns ),
					'isContent' => $namespaceInfo->isContent( $ns ),
					'isIncludable' => !$namespaceInfo->isNonincludable( $ns ),
					'isMovable' => $namespaceInfo->isMovable( $ns ),
					'isSubject' => $namespaceInfo->isSubject( $ns ),
					'isTalk' => $namespaceInfo->isTalk( $ns ),
					'defaultContentModel' => $namespaceInfo->getNamespaceContentModel( $ns ),
					'aliases' => [],
				];
				if ( $ns >= NS_MAIN ) {
					$namespaces[$ns]['subject'] = $namespaceInfo->getSubject( $ns );
					$namespaces[$ns]['talk'] = $namespaceInfo->getTalk( $ns );
					$namespaces[$ns]['associated'] = $namespaceInfo->getAssociated( $ns );
				} else {
					$namespaces[$ns]['subject'] = $ns;
				}
				$namespacesByName[strtr( $title, ' ', '_' )] = $ns;
				if ( $canonical ) {
					$namespacesByName[$canonical] = $ns;
				}
			}

			$namespaceAliases = $config->get( MainConfigNames::NamespaceAliases );
			$aliases = array_merge( $namespaceAliases, $contLang->getNamespaceAliases() );
			foreach ( $aliases as $title => $ns ) {
				if ( !isset( $namespacesByName[$title] ) && isset( $namespaces[$ns] ) ) {
					$ct = count( $namespaces[$ns]['aliases'] );
					$namespaces[$ns]['aliases'][$ct + 1] = $title;
					$namespacesByName[$title] = $ns;
				}
			}

			$namespaces[NS_MAIN]['displayName'] = wfMessage( 'blanknamespace' )->inContentLanguage()->text();

			self::$namespacesCache = $namespaces;
			self::$namespacesCacheLang = $contLang->getCode();
		}
		$info['namespaces'] = self::$namespacesCache;

		if ( defined( 'MW_PHPUNIT_TEST' ) ) {
			$info['stats'] = [
				'pages' => 1,
				'articles' => 1,
				'files' => 0,
				'edits' => 1,
				'users' => 1,
				'activeUsers' => 1,
				'admins' => 1,
			];
		} else {
			$info['stats'] = [
				'pages' => (int)SiteStats::pages(),
				'articles' => (int)SiteStats::articles(),
				'files' => (int)SiteStats::images(),
				'edits' => (int)SiteStats::edits(),
				'users' => (int)SiteStats::users(),
				'activeUsers' => (int)SiteStats::activeUsers(),
				'admins' => (int)SiteStats::numberingroup( 'sysop' ),
			];
		}

		return $this->getEngine()->registerInterface( 'mw.site.lua', $lib, $info );
	}

	/**
	 * Handler for pagesInCategory
	 * @internal
	 * @param string|null $category
	 * @param string|null $which
	 * @return int[]|int[][]
	 */
	public function pagesInCategory( $category = null, $which = null ) {
		$this->checkType( 'pagesInCategory', 1, $category, 'string' );
		$this->checkTypeOptional( 'pagesInCategory', 2, $which, 'string', 'all' );

		$title = Title::makeTitleSafe( NS_CATEGORY, $category );
		if ( !$title ) {
			return [ 0 ];
		}
		$cacheKey = $title->getDBkey();

		if ( !isset( $this->pagesInCategoryCache[$cacheKey] ) ) {
			$this->incrementExpensiveFunctionCount();
			$category = Category::newFromTitle( $title );
			$counts = [
				'all' => $category->getMemberCount(),
				'subcats' => $category->getSubcatCount(),
				'files' => $category->getFileCount(),
				'pages' => $category->getPageCount( Category::COUNT_CONTENT_PAGES ),
			];
			$this->pagesInCategoryCache[$cacheKey] = $counts;
		}
		if ( $which === '*' ) {
			return [ $this->pagesInCategoryCache[$cacheKey] ];
		}
		if ( !isset( $this->pagesInCategoryCache[$cacheKey][$which] ) ) {
			$this->checkType(
				'pagesInCategory', 2, $which, "one of '*', 'all', 'pages', 'subcats', or 'files'"
			);
		}
		return [ $this->pagesInCategoryCache[$cacheKey][$which] ];
	}

	/**
	 * Handler for pagesInNamespace
	 * @internal
	 * @param int|string|null $ns
	 * @return int[]
	 */
	public function pagesInNamespace( $ns = null ) {
		$this->checkType( 'pagesInNamespace', 1, $ns, 'number' );
		return [ (int)SiteStats::pagesInNs( intval( $ns ) ) ];
	}

	/**
	 * Handler for usersInGroup
	 * @internal
	 * @param string|null $group
	 * @return int[]
	 */
	public function usersInGroup( $group = null ) {
		$this->checkType( 'usersInGroup', 1, $group, 'string' );
		return [ (int)SiteStats::numberingroup( strtolower( $group ) ) ];
	}

	/**
	 * Handler for getNsIndex
	 * @internal
	 * @param string|null $name
	 * @return int[]|bool[]
	 */
	public function getNsIndex( $name = null ) {
		$this->checkType( 'getNsIndex', 1, $name, 'string' );
		// PHP call is case-insensitive but chokes on non-standard spaces/underscores.
		$name = trim( preg_replace( '/[\s_]+/', '_', $name ), '_' );
		return [ MediaWikiServices::getInstance()->getContentLanguage()->getNsIndex( $name ) ];
	}

	/**
	 * Handler for interwikiMap
	 * @internal
	 * @param string|null $filter
	 * @return array[]
	 */
	public function interwikiMap( $filter = null ) {
		$this->checkTypeOptional( 'interwikiMap', 1, $filter, 'string', null );
		$local = null;
		if ( $filter === 'local' ) {
			$local = true;
		} elseif ( $filter === '!local' ) {
			$local = false;
		} elseif ( $filter !== null ) {
			throw new LuaError(
				"bad argument #1 to 'interwikiMap' (unknown filter '$filter')"
			);
		}
		$cacheKey = $filter ?? 'null';
		if ( !isset( self::$interwikiMapCache[$cacheKey] ) ) {
			// Not expensive because we can have a max of three cache misses in the
			// entire page parse.
			$interwikiMap = [];
			$lookup = MediaWikiServices::getInstance()->getInterwikiLookup();
			$prefixes = $lookup->getAllPrefixes( $local );
			$config = MediaWikiServices::getInstance()->getMainConfig();
			$localInterwikis = $config->get( MainConfigNames::LocalInterwikis );
			$extraInterlanguageLinkPrefixes = $config->get( MainConfigNames::ExtraInterlanguageLinkPrefixes );
			foreach ( $prefixes as $row ) {
				$prefix = $row['iw_prefix'];
				$val = [
					'prefix' => $prefix,
					'url' => wfExpandUrl( $row['iw_url'], PROTO_RELATIVE ),
					'isProtocolRelative' => substr( $row['iw_url'], 0, 2 ) === '//',
					'isLocal' => isset( $row['iw_local'] ) && $row['iw_local'] == '1',
					'isTranscludable' => isset( $row['iw_trans'] ) && $row['iw_trans'] == '1',
					'isCurrentWiki' => in_array( $prefix, $localInterwikis ),
					'isExtraLanguageLink' => in_array( $prefix, $extraInterlanguageLinkPrefixes ),
				];
				if ( $val['isExtraLanguageLink'] ) {
					$displayText = wfMessage( "interlanguage-link-$prefix" );
					if ( !$displayText->isDisabled() ) {
						$val['displayText'] = $displayText->text();
					}
					$tooltip = wfMessage( "interlanguage-link-sitename-$prefix" );
					if ( !$tooltip->isDisabled() ) {
						$val['tooltip'] = $tooltip->text();
					}
				}
				$interwikiMap[$prefix] = $val;
			}
			self::$interwikiMapCache[$cacheKey] = $interwikiMap;
		}
		return [ self::$interwikiMapCache[$cacheKey] ];
	}
}

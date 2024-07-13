<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 */

namespace MediaWiki\Extension\Gadgets;

use InvalidArgumentException;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Title\Title;
use ObjectCache;
use TextContent;
use WANObjectCache;
use Wikimedia\Rdbms\Database;
use Wikimedia\Rdbms\IConnectionProvider;

/**
 * Gadgets repo powered by MediaWiki:Gadgets-definition
 */
class MediaWikiGadgetsDefinitionRepo extends GadgetRepo {
	private const CACHE_VERSION = 4;

	/** @var array|null */
	private $definitions;

	private IConnectionProvider $dbProvider;
	private WANObjectCache $wanCache;
	private RevisionLookup $revLookup;

	public function __construct(
		IConnectionProvider $dbProvider,
		WANObjectCache $wanCache,
		RevisionLookup $revLookup
	) {
		$this->dbProvider = $dbProvider;
		$this->wanCache = $wanCache;
		$this->revLookup = $revLookup;
	}

	/**
	 * @param string $id
	 * @throws InvalidArgumentException
	 * @return Gadget
	 */
	public function getGadget( string $id ): Gadget {
		$gadgets = $this->loadGadgets();
		if ( !isset( $gadgets[$id] ) ) {
			throw new InvalidArgumentException( "No gadget registered for '$id'" );
		}

		return new Gadget( $gadgets[$id] );
	}

	public function getGadgetIds(): array {
		return array_keys( $this->loadGadgets() );
	}

	public function handlePageUpdate( LinkTarget $target ): void {
		if ( $target->inNamespace( NS_MEDIAWIKI ) && $target->getDBkey() === 'Gadgets-definition' ) {
			$this->purgeDefinitionCache();
		}
	}

	/**
	 * Purge the definitions cache, for example when MediaWiki:Gadgets-definition is edited.
	 */
	private function purgeDefinitionCache(): void {
		$srvCache = ObjectCache::getLocalServerInstance( CACHE_HASH );
		$key = $this->makeDefinitionCacheKey( $this->wanCache );

		$this->wanCache->delete( $key );
		$srvCache->delete( $key );
		$this->definitions = null;
	}

	/**
	 * @param WANObjectCache $cache
	 * @return string
	 */
	private function makeDefinitionCacheKey( WANObjectCache $cache ) {
		return $cache->makeKey(
			'gadgets-definition',
			Gadget::GADGET_CLASS_VERSION,
			self::CACHE_VERSION
		);
	}

	/**
	 * Get list of gadgets.
	 *
	 * @return array[] List of Gadget objects
	 */
	protected function loadGadgets(): array {
		if ( defined( 'MW_PHPUNIT_TEST' ) && MediaWikiServices::getInstance()->isStorageDisabled() ) {
			// Bail out immediately if storage is disabled. This should never happen in normal operations, but can
			// happen a lot in tests: this method is called from the UserGetDefaultOptions hook handler, so any test
			// that uses UserOptionsLookup will end up reaching this code, which is problematic if the test is not
			// in the Database group (T155147).
			return [];
		}
		// From back to front:
		//
		// 3. wan cache (e.g. memcached)
		//    This improves end-user latency and reduces database load.
		//    It is purged when the data changes.
		//
		// 2. server cache (e.g. APCu).
		//    Very short blind TTL, mainly to avoid high memcached I/O.
		//
		// 1. process cache. Faster repeat calls.
		if ( $this->definitions === null ) {
			$wanCache = $this->wanCache;
			$srvCache = ObjectCache::getLocalServerInstance( CACHE_HASH );
			$key = $this->makeDefinitionCacheKey( $wanCache );
			$this->definitions = $srvCache->getWithSetCallback(
				$key,
				// between 7 and 15 seconds to avoid memcached/lockTSE stampede (T203786)
				mt_rand( 7, 15 ),
				function () use ( $wanCache, $key ) {
					return $wanCache->getWithSetCallback(
						$key,
						// 1 day
						Gadget::CACHE_TTL,
						function ( $old, &$ttl, &$setOpts ) {
							// Reduce caching of known-stale data (T157210)
							$setOpts += Database::getCacheSetOptions( $this->dbProvider->getReplicaDatabase() );

							return $this->fetchStructuredList();
						},
						[
							'version' => 2,
							// Avoid database stampede
							'lockTSE' => 300,
						 ]
					);
				}
			);
		}
		return $this->definitions;
	}

	/**
	 * Fetch list of gadgets and returns it as associative array of sections with gadgets
	 * e.g. [ $name => $gadget1, etc. ]
	 * @return array[]
	 */
	public function fetchStructuredList() {
		// T157210: avoid using wfMessage() to avoid staleness due to cache layering
		$title = Title::makeTitle( NS_MEDIAWIKI, 'Gadgets-definition' );
		$revRecord = $this->revLookup->getRevisionByTitle( $title );
		if ( !$revRecord
			|| !$revRecord->getContent( SlotRecord::MAIN )
			|| $revRecord->getContent( SlotRecord::MAIN )->isEmpty()
		) {
			return [];
		}

		$content = $revRecord->getContent( SlotRecord::MAIN );
		$g = ( $content instanceof TextContent ) ? $content->getText() : '';

		$gadgets = $this->listFromDefinition( $g );

		wfDebug( __METHOD__ . ": MediaWiki:Gadgets-definition parsed, cache entry should be updated\n" );

		return $gadgets;
	}

	/**
	 * Generates a structured list of Gadget objects from a definition
	 *
	 * @param string $definition
	 * @return array[] List of Gadget objects indexed by the gadget's name.
	 */
	private function listFromDefinition( $definition ): array {
		$definition = preg_replace( '/<!--.*?-->/s', '', $definition );
		$lines = preg_split( '/(\r\n|\r|\n)+/', $definition );

		$gadgets = [];
		$section = '';

		foreach ( $lines as $line ) {
			$m = [];
			if ( preg_match( '/^==+ *([^*:\s|]+)\s*(?<!=)==+\s*$/', $line, $m ) ) {
				$section = $m[1];
			} else {
				$gadget = $this->newFromDefinition( $line, $section );
				if ( $gadget ) {
					$gadgets[$gadget->getName()] = $gadget->toArray();
				}
			}
		}

		return $gadgets;
	}

	/**
	 * Creates an instance of this class from definition in MediaWiki:Gadgets-definition
	 * @param string $definition Gadget definition
	 * @param string $category
	 * @return Gadget|false Instance of Gadget class or false if $definition is invalid
	 */
	public function newFromDefinition( $definition, $category ) {
		if ( !preg_match(
			'/^\*+ *([a-zA-Z](?:[-_:.\w ]*[a-zA-Z0-9])?)(\s*\[.*?\])?\s*((\|[^|]*)+)\s*$/',
			$definition,
			$matches
		) ) {
			return false;
		}
		[ , $name, $options, $pages ] = $matches;
		$options = trim( $options, ' []' );

		// NOTE: the gadget name is used as part of the name of a form field,
		// and must follow the rules defined in https://www.w3.org/TR/html4/types.html#type-cdata
		// Also, title-normalization applies.
		$name = str_replace( ' ', '_', $name );
		// If the name is too long, then RL will throw an exception when
		// we try to register the module
		if ( !Gadget::isValidGadgetID( $name ) ) {
			return false;
		}

		$info = [
			'category' => $category,
			'name' => $name,
			'definition' => $definition,
		];

		foreach ( preg_split( '/\s*\|\s*/', $options, -1, PREG_SPLIT_NO_EMPTY ) as $option ) {
			$arr = preg_split( '/\s*=\s*/', $option, 2 );
			$option = $arr[0];
			if ( isset( $arr[1] ) ) {
				$params = explode( ',', $arr[1] );
				$params = array_map( 'trim', $params );
			} else {
				$params = [];
			}

			switch ( $option ) {
				case 'ResourceLoader':
					$info['resourceLoaded'] = true;
					break;
				case 'requiresES6':
					$info['requiresES6'] = true;
					break;
				case 'dependencies':
					$info['dependencies'] = $params;
					break;
				case 'peers':
					$info['peers'] = $params;
					break;
				case 'rights':
					$info['requiredRights'] = $params;
					break;
				case 'hidden':
					$info['hidden'] = true;
					break;
				case 'actions':
					$info['requiredActions'] = $params;
					break;
				case 'skins':
					$info['requiredSkins'] = $params;
					break;
				case 'namespaces':
					$info['requiredNamespaces'] = $params;
					break;
				case 'categories':
					$info['requiredCategories'] = $params;
					break;
				case 'contentModels':
					$info['requiredContentModels'] = $params;
					break;
				case 'default':
					$info['onByDefault'] = true;
					break;
				case 'package':
					$info['package'] = true;
					break;
				case 'type':
					// Single value, not a list
					$info['type'] = $params[0] ?? '';
					break;
				case 'supportsUrlLoad':
					$val = $params[0] ?? '';
					$info['supportsUrlLoad'] = $val !== 'false';
					break;
			}
		}

		foreach ( preg_split( '/\s*\|\s*/', $pages, -1, PREG_SPLIT_NO_EMPTY ) as $page ) {
			$info['pages'][] = self::RESOURCE_TITLE_PREFIX . trim( $page );
		}

		return new Gadget( $info );
	}
}

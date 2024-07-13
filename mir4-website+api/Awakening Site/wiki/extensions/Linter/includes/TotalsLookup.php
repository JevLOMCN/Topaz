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

namespace MediaWiki\Linter;

use MediaWiki\MediaWikiServices;
use MediaWiki\WikiMap\WikiMap;
use WANObjectCache;
use Wikimedia\Rdbms\Database as MWDatabase;

/**
 * Lookup to find and cache the total amount of
 * lint errors in each category
 */
class TotalsLookup {

	/**
	 * @var WANObjectCache
	 */
	private $cache;

	/**
	 * @var CategoryManager
	 */
	private $catManager;

	/**
	 * @var Database
	 */
	private $db;

	/**
	 * @param CategoryManager $catManager
	 * @param WANObjectCache $cache
	 * @param Database $db
	 */
	public function __construct(
		CategoryManager $catManager, WANObjectCache $cache, Database $db
	) {
		$this->cache = $cache;
		$this->catManager = $catManager;
		$this->db = $db;
	}

	/**
	 * @param string $cat
	 * @return string
	 */
	private function makeKey( $cat ) {
		return $this->cache->makeKey( 'linter', 'total', $cat );
	}

	/**
	 * Get the totals for every category in the database
	 *
	 * @return array
	 */
	public function getTotals() {
		$cats = $this->catManager->getVisibleCategories();
		$db = $this->db;
		$fetchedTotals = false;
		$totals = [];
		foreach ( $cats as $cat ) {
			$totals[$cat] = $this->cache->getWithSetCallback(
				$this->makeKey( $cat ),
				WANObjectCache::TTL_INDEFINITE,
				static function ( $oldValue, &$ttl, &$setOpts, $oldAsOf ) use ( $cat, $db, &$fetchedTotals ) {
					$setOpts += MWDatabase::getCacheSetOptions(
						Database::getDBConnectionRef( DB_REPLICA )
					);
					if ( $fetchedTotals === false ) {
						$fetchedTotals = $db->getTotals();
					}
					return $fetchedTotals[$cat];
				},
				[
					'checkKeys' => [
						$this->cache->makeKey( 'linter', 'totals' ),
						$this->makeKey( $cat ),
					],
					'lockTSE' => 30,
				]
			);
		}

		return $totals;
	}

	/**
	 * Send stats to statsd and update totals cache
	 *
	 * @param array $changes
	 */
	public function updateStats( array $changes ) {
		$mwServices = MediaWikiServices::getInstance();
		$linterStatsdSampleFactor = $mwServices->getMainConfig()->get( 'LinterStatsdSampleFactor' );

		if ( $linterStatsdSampleFactor === false ) {
			// Don't send to statsd, but update cache with $changes
			$raw = $changes['added'];
			foreach ( $changes['deleted'] as $cat => $count ) {
				if ( isset( $raw[$cat] ) ) {
					$raw[$cat] -= $count;
				} else {
					// Negative value
					$raw[$cat] = 0 - $count;
				}
			}

			foreach ( $raw as $cat => $count ) {
				if ( $count != 0 ) {
					// There was a change in counts, invalidate the cache
					$this->touchCategoryCache( $cat );
				}
			}
			return;
		} elseif ( mt_rand( 1, $linterStatsdSampleFactor ) != 1 ) {
			return;
		}

		$totals = $this->db->getTotals();
		$wiki = WikiMap::getCurrentWikiId();
		$stats = $mwServices->getStatsdDataFactory();
		foreach ( $totals as $name => $count ) {
			$stats->gauge( "linter.category.$name.$wiki", $count );
		}
		$stats->gauge( "linter.totals.$wiki", array_sum( $totals ) );

		$this->touchAllCategoriesCache();
	}

	/**
	 * Have a single category be recalculated
	 *
	 * @param string $cat category name
	 */
	public function touchCategoryCache( $cat ) {
		$this->cache->touchCheckKey( $this->makeKey( $cat ) );
	}

	/**
	 * Have all categories be recalculated
	 */
	public function touchAllCategoriesCache() {
		$this->cache->touchCheckKey( $this->cache->makeKey( 'linter', 'totals' ) );
	}
}

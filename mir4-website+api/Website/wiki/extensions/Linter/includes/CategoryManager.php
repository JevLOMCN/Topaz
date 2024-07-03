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

use InvalidArgumentException;
use MediaWiki\MediaWikiServices;

/**
 * Functions for lint error categories
 */
class CategoryManager {

	private const HIGH = 'high';
	private const MEDIUM = 'medium';
	private const LOW = 'low';
	private const NONE = 'none';

	/**
	 * Map of category names to their hardcoded
	 * numerical ids for use in the database
	 *
	 * @var int[]
	 */
	private $categoryIds = [];

	/**
	 * @var string[][]
	 */
	private $categories = [
		self::HIGH => [],
		self::MEDIUM => [],
		self::LOW => [],
		self::NONE => [],
	];

	/**
	 * @var bool[]
	 * @phan-var array<string,bool>
	 */
	private $hasNameParam = [];

	/**
	 * @var bool[]
	 * @phan-var array<string,bool>
	 */
	private $hasNoParams = [];

	public function __construct() {
		$mwServices = MediaWikiServices::getInstance();
		$linterCategories = $mwServices->getMainConfig()->get( 'LinterCategories' );

		foreach ( $linterCategories as $name => $info ) {
			if ( $info['enabled'] ) {
				$this->categories[$info['priority']][] = $name;
			}
			if ( $info['has-name'] ?? false ) {
				$this->hasNameParam[$name] = true;
			}
			if ( $info['no-params'] ?? false ) {
				$this->hasNoParams[$name] = true;
			}
			if ( isset( $info['dbid'] ) ) {
				if ( isset( $this->categoryIds[$name] ) ) {
					throw new InvalidArgumentException( "duplicate ID: $name" );
				}
				$this->categoryIds[$name] = $info['dbid'];
			}
		}

		sort( $this->categories[self::HIGH] );
		sort( $this->categories[self::MEDIUM] );
		sort( $this->categories[self::LOW] );
		sort( $this->categories[self::NONE] );
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function hasNameParam( $name ) {
		return isset( $this->hasNameParam[$name] );
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function hasNoParams( $name ) {
		return isset( $this->hasNoParams[$name] );
	}

	/**
	 * @return string[]
	 */
	public function getHighPriority() {
		return $this->categories[self::HIGH];
	}

	/**
	 * @return string[]
	 */
	public function getMediumPriority() {
		return $this->categories[self::MEDIUM];
	}

	/**
	 * @return string[]
	 */
	public function getLowPriority() {
		return $this->categories[self::LOW];
	}

	/**
	 * @return string[]
	 */
	public function getNonePriority() {
		return $this->categories[self::NONE];
	}

	/**
	 * Categories that are configured to be displayed to users
	 *
	 * @return string[]
	 */
	public function getVisibleCategories() {
		return array_merge(
			$this->categories[self::HIGH],
			$this->categories[self::MEDIUM],
			$this->categories[self::LOW]
		);
	}

	/**
	 * Categories that are configured to not be displayed to users
	 *
	 * @return string[]
	 */
	public function getInvisibleCategories() {
		return $this->categories[self::NONE];
	}

	/**
	 * Whether this category has a hardcoded id and can be
	 * inserted into the database
	 *
	 * @param string $name
	 * @return bool
	 */
	public function isKnownCategory( $name ) {
		return isset( $this->categoryIds[$name] );
	}

	/**
	 * @param int $id
	 * @return string
	 * @throws MissingCategoryException if we can't find the name for the id
	 */
	public function getCategoryName( $id ) {
		$flip = array_flip( $this->categoryIds );
		if ( isset( $flip[$id] ) ) {
			return $flip[$id];
		}

		throw new MissingCategoryException( "Could not find name for id $id" );
	}

	/**
	 * @param string[] $names
	 * @return int[]
	 */
	public function getCategoryIds( array $names ) {
		$result = [];
		foreach ( $names as $name ) {
			$result[$name] = $this->getCategoryId( $name );
		}

		return $result;
	}

	/**
	 * Get the int id for the category in lint_categories table
	 *
	 * @param string $name
	 * @param int|null $hint An optional hint, passed along from Parsoid.
	 *   If the hint contains a suggested category ID but the Linter
	 *   extension doesn't (yet) have one, use the ID from Parsoid's hint.
	 *   This allows decoupling the Parsoid deploy of a new category
	 *   from the corresponding Linter extension deploy.
	 * @return int
	 * @throws MissingCategoryException if we can't find the id for the name
	 *   and there is no hint from Parsoid
	 */
	public function getCategoryId( $name, $hint = null ) {
		if ( isset( $this->categoryIds[$name] ) ) {
			return $this->categoryIds[$name];
		}

		// Use hint from Parsoid, if available.
		if ( $hint !== null ) {
			return $hint;
		}

		throw new MissingCategoryException( "Cannot find id for '$name'" );
	}
}

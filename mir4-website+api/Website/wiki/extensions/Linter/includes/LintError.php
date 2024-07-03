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

use FormatJson;

/**
 * Model to represent a LintError
 */
class LintError {
	/**
	 * @var string
	 */
	public $category;

	/**
	 * @var int[] [ start, end ]
	 */
	public $location;

	/**
	 * @var int
	 */
	public $lintId;

	/**
	 * @var array
	 */
	public $params;

	/**
	 * @var array|null
	 */
	public $templateInfo;

	/**
	 * @var string|null
	 */
	public $tagInfo;

	/**
	 * Optional hint for the linter category ID.
	 * Passed through from Parsoid when a new category has been added
	 * that isn't known to PHP yet.
	 * @var int|null
	 */
	public $catId;

	/**
	 * @param string $category
	 * @param int[] $location [ start, end ]
	 * @param string|array $params JSON string or already decoded array
	 * @param int|null $catId Optional category ID hint
	 * @param int $lintId Optional linter_id
	 */
	public function __construct( $category, $location, $params, $catId = null, $lintId = 0 ) {
		$this->category = $category;
		$this->catId = $catId;

		if ( is_string( $params ) ) {
			$params = FormatJson::decode( $params, true );
		}
		$this->params = $params;
		$this->lintId = $lintId;
		$this->location = $location;
		// Convenient accessors for all errors
		$this->templateInfo = $params['templateInfo'] ?? null;
		$this->tagInfo = $params['name'] ?? null;
	}

	/**
	 * Unique id to identify this error, for internal
	 * purposes
	 *
	 * @return string
	 */
	public function id() {
		return $this->category . ",{$this->location[0]},{$this->location[1]},"
			. FormatJson::encode( $this->params );
	}

	/**
	 * Get the params that are extra for this error,
	 * not part of the default set
	 *
	 * @return array
	 */
	public function getExtraParams() {
		$params = $this->params;
		unset( $params['templateInfo'] );

		return $params;
	}
}

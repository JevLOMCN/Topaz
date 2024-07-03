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

use ApiQuery;
use ApiQueryBase;
use ApiResult;
use WANObjectCache;

class ApiQueryLinterStats extends ApiQueryBase {

	private WANObjectCache $cache;

	/**
	 * @param ApiQuery $queryModule
	 * @param string $moduleName
	 * @param WANObjectCache $cache
	 */
	public function __construct( ApiQuery $queryModule, string $moduleName, WANObjectCache $cache ) {
		parent::__construct( $queryModule, $moduleName, 'lntrst' );
		$this->cache = $cache;
	}

	/**
	 * Add totals to output
	 */
	public function execute() {
		$totalsLookup = new TotalsLookup(
			new CategoryManager(),
			$this->cache,
			new Database( 0 )
		);
		$totals = $totalsLookup->getTotals();
		ApiResult::setArrayType( $totals, 'assoc' );
		$this->getResult()->addValue( [ 'query', 'linterstats' ], 'totals', $totals );
	}

	/** @inheritDoc */
	public function getExamplesMessages() {
		return [
			'action=query&meta=linterstats' =>
				'apihelp-query+linterstats-example-1',
		];
	}
}

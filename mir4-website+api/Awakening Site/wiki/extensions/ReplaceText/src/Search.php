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
 * https://www.gnu.org/copyleft/gpl.html
 *
 * @file
 */
namespace MediaWiki\Extension\ReplaceText;

use MediaWiki\Config\Config;
use MediaWiki\Title\Title;
use Wikimedia\Rdbms\IConnectionProvider;
use Wikimedia\Rdbms\IReadableDatabase;
use Wikimedia\Rdbms\IResultWrapper;

class Search {
	private Config $config;
	private IConnectionProvider $loadBalancer;

	public function __construct(
		Config $config,
		IConnectionProvider $loadBalancer
	) {
		$this->config = $config;
		$this->loadBalancer = $loadBalancer;
	}

	/**
	 * @param string $search
	 * @param array $namespaces
	 * @param string|null $category
	 * @param string|null $prefix
	 * @param int|null $pageLimit
	 * @param bool $use_regex
	 * @return IResultWrapper Resulting rows
	 */
	public function doSearchQuery(
		$search, $namespaces, $category, $prefix, $pageLimit, $use_regex = false
	) {
		$dbr = $this->loadBalancer->getReplicaDatabase();
		$tables = [ 'page', 'revision', 'text', 'slots', 'content' ];
		$vars = [ 'page_id', 'page_namespace', 'page_title', 'old_text', 'slot_role_id' ];
		if ( $use_regex ) {
			$comparisonCond = self::regexCond( $dbr, 'old_text', $search );
		} else {
			$any = $dbr->anyString();
			$comparisonCond = 'old_text ' . $dbr->buildLike( $any, $search, $any );
		}
		$conds = [
			$comparisonCond,
			'page_namespace' => $namespaces,
			'rev_id = page_latest',
			'rev_id = slot_revision_id',
			'slot_content_id = content_id',
			$dbr->buildIntegerCast( 'SUBSTR(content_address, 4)' ) . ' = old_id'
		];
		if ( $pageLimit === null || $pageLimit === '' ) {
			$pageLimit = $this->config->get( 'ReplaceTextResultsLimit' );
		}
		self::categoryCondition( $category, $tables, $conds );
		$this->prefixCondition( $prefix, $conds );
		$options = [
			'ORDER BY' => 'page_namespace, page_title',
			'LIMIT' => $pageLimit
		];

		return $dbr->select( $tables, $vars, $conds, __METHOD__, $options );
	}

	/**
	 * @param string|null $category
	 * @param array &$tables
	 * @param array &$conds
	 */
	public static function categoryCondition( $category, &$tables, &$conds ) {
		if ( strval( $category ) !== '' ) {
			$category = Title::newFromText( $category )->getDbKey();
			$tables[] = 'categorylinks';
			$conds[] = 'page_id = cl_from';
			$conds['cl_to'] = $category;
		}
	}

	/**
	 * @param string|null $prefix
	 * @param array &$conds
	 */
	private function prefixCondition( $prefix, &$conds ) {
		if ( strval( $prefix ) === '' ) {
			return;
		}

		$dbr = $this->loadBalancer->getReplicaDatabase();
		$title = Title::newFromText( $prefix );
		if ( $title !== null ) {
			$prefix = $title->getDbKey();
		}
		$any = $dbr->anyString();
		// @phan-suppress-next-line PhanTypeMismatchArgumentNullable strval makes this non-null
		$conds[] = 'page_title ' . $dbr->buildLike( $prefix, $any );
	}

	/**
	 * @param IReadableDatabase $dbr
	 * @param string $column
	 * @param string $regex
	 * @return string query condition for regex
	 */
	public static function regexCond( $dbr, $column, $regex ) {
		if ( $dbr->getType() == 'postgres' ) {
			$cond = "$column ~ ";
		} else {
			$cond = "CAST($column AS BINARY) REGEXP BINARY ";
		}
		$cond .= $dbr->addQuotes( $regex );
		return $cond;
	}

	/**
	 * @param string $str
	 * @param array $namespaces
	 * @param string|null $category
	 * @param string|null $prefix
	 * @param int|null $pageLimit
	 * @param bool $use_regex
	 * @return IResultWrapper Resulting rows
	 */
	public function getMatchingTitles(
		$str,
		$namespaces,
		$category,
		$prefix,
		$pageLimit,
		$use_regex = false
	) {
		$dbr = $this->loadBalancer->getReplicaDatabase();

		$tables = [ 'page' ];
		$vars = [ 'page_title', 'page_namespace' ];

		$str = str_replace( ' ', '_', $str );
		if ( $use_regex ) {
			$comparisonCond = self::regexCond( $dbr, 'page_title', $str );
		} else {
			$any = $dbr->anyString();
			$comparisonCond = 'page_title ' . $dbr->buildLike( $any, $str, $any );
		}
		$conds = [
			$comparisonCond,
			'page_namespace' => $namespaces,
		];
		if ( $pageLimit === null || $pageLimit === '' ) {
			$pageLimit = $this->config->get( 'ReplaceTextResultsLimit' );
		}
		self::categoryCondition( $category, $tables, $conds );
		$this->prefixCondition( $prefix, $conds );
		$sort = [ 'ORDER BY' => 'page_namespace, page_title', 'LIMIT' => $pageLimit ];

		return $dbr->select( $tables, $vars, $conds, __METHOD__, $sort );
	}

	/**
	 * Do a replacement on a string.
	 * @param string $text
	 * @param string $search
	 * @param string $replacement
	 * @param bool $regex
	 * @return string
	 */
	public static function getReplacedText( $text, $search, $replacement, $regex ) {
		if ( $regex ) {
			$escapedSearch = addcslashes( $search, '/' );
			return preg_replace( "/$escapedSearch/Uu", $replacement, $text );
		} else {
			return str_replace( $search, $replacement, $text );
		}
	}

	/**
	 * Do a replacement on a title.
	 * @param Title $title
	 * @param string $search
	 * @param string $replacement
	 * @param bool $regex
	 * @return Title|null
	 */
	public static function getReplacedTitle( Title $title, $search, $replacement, $regex ) {
		$oldTitleText = $title->getText();
		$newTitleText = self::getReplacedText( $oldTitleText, $search, $replacement, $regex );
		return Title::makeTitleSafe( $title->getNamespace(), $newTitleText );
	}
}

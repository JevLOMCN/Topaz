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

use IContextSource;
use InvalidArgumentException;
use LinkCache;
use MediaWiki\Html\Html;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\MediaWikiServices;
use MediaWiki\Pager\TablePager;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleValue;
use Wikimedia\Rdbms\IExpression;
use Wikimedia\Rdbms\LikeValue;
use Wikimedia\Rdbms\SelectQueryBuilder;

class LintErrorsPager extends TablePager {

	/**
	 * @var CategoryManager
	 */
	private $categoryManager;

	/**
	 * @var string
	 */
	private $category;

	/**
	 * @var int|null
	 */
	private $categoryId;

	/**
	 * @var LinkRenderer
	 */
	private $linkRenderer;

	/**
	 * @var array
	 */
	private $namespaces;

	/**
	 * @var bool
	 */
	private $exactMatch;

	/**
	 * @var string
	 */
	private $title;

	/**
	 * Allowed values are keys 'all', 'with' or 'without'
	 * @var string
	 */
	private $throughTemplate;

	/**
	 * @var string
	 */
	private $tag;

	/**
	 * @param IContextSource $context
	 * @param string|null $category
	 * @param LinkRenderer $linkRenderer
	 * @param CategoryManager $catManager
	 * @param array $namespaces
	 * @param bool $exactMatch
	 * @param string $title
	 * @param string $throughTemplate
	 * @param string $tag
	 */
	public function __construct( IContextSource $context, $category, LinkRenderer $linkRenderer,
		CategoryManager $catManager, $namespaces, $exactMatch, $title, $throughTemplate, $tag
	) {
		$this->category = $category;
		$this->categoryManager = $catManager;
		if ( $category !== null ) {
			$this->categoryId = $catManager->getCategoryId( $this->category );
		} else {
			$this->categoryId = null;
		}
		$this->linkRenderer = $linkRenderer;
		$this->namespaces = $namespaces;
		$this->exactMatch = $exactMatch;
		$this->title = $title;
		$this->throughTemplate = $throughTemplate ?: 'all';
		$this->tag = $tag ?: 'all';
		parent::__construct( $context );
	}

	private function fillQueryBuilder( SelectQueryBuilder $queryBuilder ): void {
		$mainConfig = MediaWikiServices::getInstance()->getMainConfig();
		$queryBuilder
			->table( 'page' )
			->join( 'linter', null, 'page_id=linter_page' )
			->fields( LinkCache::getSelectFields() )
			->fields( [
				'page_namespace', 'page_title',
				'linter_id', 'linter_params',
				'linter_start', 'linter_end',
				'linter_cat'
			] );

		if ( $this->categoryId !== null ) {
			$queryBuilder->where( [ 'linter_cat' => $this->categoryId ] );
		}

		if ( $this->title !== '' ) {
			$namespaces = $this->namespaces ?: [ NS_MAIN ];
			// Specify page_namespace so that the index can be used (T360865)
			$queryBuilder->where( [ 'page_namespace' => $namespaces ] );
			if ( $mainConfig->get( 'LinterUseNamespaceColumnStage' ) ) {
				// Also put a condition on linter_namespace, in case the DB
				// decides to put the linter table first
				$queryBuilder->where( [ 'linter_namespace' => $namespaces ] );
			}
			if ( $this->exactMatch ) {
				$queryBuilder->where( [
					'page_title' => $this->title
				] );
			} else {
				$queryBuilder->where( $this->mDb->expr(
					'page_title', IExpression::LIKE, new LikeValue( $this->title, $this->mDb->anyString() )
				) );
			}
		} elseif ( $this->namespaces ) {
			$namespaceCol = $mainConfig->get( 'LinterUseNamespaceColumnStage' )
				? "linter_namespace" : "page_namespace";
			$queryBuilder->where( [ $namespaceCol => $this->namespaces ] );
		}

		if ( $mainConfig->get( 'LinterUserInterfaceTagAndTemplateStage' ) ) {
			if ( $this->throughTemplate !== 'all' ) {
				$op = ( $this->throughTemplate === 'with' ) ? '!=' : '=';
				$queryBuilder->where( $this->mDb->expr( 'linter_template', $op, '' ) );
			}
			if ( $this->tag !== 'all' && ( new HtmlTags( $this ) )->checkAllowedHTMLTags( $this->tag ) ) {
				$queryBuilder->where( [ 'linter_tag'  => $this->tag ] );
			}
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getQueryInfo() {
		$queryBuilder = $this->mDb->newSelectQueryBuilder();
		$this->fillQueryBuilder( $queryBuilder );
		return $queryBuilder->getQueryInfo();
	}

	protected function doBatchLookups() {
		$linkCache = MediaWikiServices::getInstance()->getLinkCache();
		foreach ( $this->mResult as $row ) {
			$titleValue = new TitleValue( (int)$row->page_namespace, $row->page_title );
			$linkCache->addGoodLinkObjFromRow( $titleValue, $row );
		}
	}

	/** @inheritDoc */
	public function isFieldSortable( $field ) {
		return false;
	}

	/**
	 * @param string $name
	 * @param string $value
	 * @return string
	 * @throws InvalidArgumentException
	 */
	public function formatValue( $name, $value ) {
		$row = $this->mCurrentRow;

		// To support multiple lint errors of varying types for a single page, the
		// category is set each time based on the category set in the lint error $row
		// not by the class when lints are being reported by type for many pages
		if ( $this->category === null && $row->linter_cat !== null ) {
			$category = $this->categoryManager->getCategoryName( $row->linter_cat );
		} else {
			$category = $this->category;
			$row->linter_cat = $this->categoryId;
		}
		$lintError = Database::makeLintError( $row );

		if ( !$lintError ) {
			return '';
		}

		switch ( $name ) {
			case 'title':
				$title = Title::makeTitle( $row->page_namespace, $row->page_title );
				$viewLink = $this->linkRenderer->makeLink( $title );
				$permManager = MediaWikiServices::getInstance()->getPermissionManager();
				$editMsgKey = $permManager->quickUserCan( 'edit', $this->getUser(), $title ) ?
					'linter-page-edit' : 'linter-page-viewsource';
				$editLink = $this->linkRenderer->makeLink(
					$title,
					$this->msg( $editMsgKey )->text(),
					[],
					[ 'action' => 'edit', 'lintid' => $lintError->lintId, ]
				);

				$historyLink = $this->linkRenderer->makeLink(
					$title,
					$this->msg( 'linter-page-history' )->text(),
					[],
					[ 'action' => 'history' ]
				);

				$editHistLinks = $this->getLanguage()->pipeList( [ $editLink, $historyLink ] );
				return $this->msg( 'linter-page-title-edit' )
					->rawParams( $viewLink, $editHistLinks )
					->escaped();
			case 'details':
				if ( $this->categoryManager->hasNameParam( $category ) &&
					isset( $lintError->params['name'] ) ) {
					return Html::element( 'code', [], $lintError->params['name'] );
				} elseif ( $category === 'bogus-image-options' && isset( $lintError->params['items'] ) ) {
					$list = array_map( static function ( $in ) {
						return Html::element( 'code', [], $in );
					}, $lintError->params['items'] );
					return $this->getLanguage()->commaList( $list );
				} elseif ( $category === 'pwrap-bug-workaround' &&
					isset( $lintError->params['root'] ) &&
					isset( $lintError->params['child'] ) ) {
					return Html::element( 'code', [],
						$lintError->params['root'] . " > " . $lintError->params['child'] );
				} elseif ( $category === 'tidy-whitespace-bug' &&
					isset( $lintError->params['node'] ) &&
					isset( $lintError->params['sibling'] ) ) {
					return Html::element( 'code', [],
						$lintError->params['node'] . " + " . $lintError->params['sibling'] );
				} elseif ( $category === 'multi-colon-escape' &&
					isset( $lintError->params['href'] ) ) {
					return Html::element( 'code', [], $lintError->params['href'] );
				} elseif ( $category === 'multiline-html-table-in-list' ) {
					/* ancestor and name will be set */
					return Html::element( 'code', [],
						$lintError->params['ancestorName'] . " > " . $lintError->params['name'] );
				} elseif ( $category === 'misc-tidy-replacement-issues' ) {
					/* There will be a 'subtype' param to disambiguate */
					return Html::element( 'code', [], $lintError->params['subtype'] );
				}
				return '';
			case 'template':
				if ( !$lintError->templateInfo ) {
					return '&mdash;';
				}

				if ( isset( $lintError->templateInfo['multiPartTemplateBlock'] ) ) {
					return $this->msg( 'multi-part-template-block' )->escaped();
				} else {
					// @phan-suppress-next-line PhanTypeArraySuspiciousNullable Null checked above
					$templateName = $lintError->templateInfo['name'];
					// Parsoid provides us with fully qualified template title
					// So, fallback to the default main namespace
					$templateTitle = Title::newFromText( $templateName );
					if ( !$templateTitle ) {
						// Shouldn't be possible...???
						return '&mdash;';
					}
				}

				return $this->linkRenderer->makeLink(
					$templateTitle
				);
			case 'category':
				return Html::element( 'code', [], $category );
			default:
				throw new InvalidArgumentException( "Unexpected name: $name" );
		}
	}

	/** @inheritDoc */
	public function getDefaultSort() {
		return 'linter_id';
	}

	/**
	 * @return string[]
	 */
	public function getFieldNames() {
		$names = [
			'title' => $this->msg( 'linter-pager-title-header' )->text(),
		];
		if ( !$this->category ) {
			$names['category'] = $this->msg( 'linter-pager-category-header' )->text();
			$names['details'] = $this->msg( "linter-pager-details-header" )->text();
		} elseif ( !$this->categoryManager->hasNoParams( $this->category ) ) {
			$names['details'] = $this->msg( "linter-pager-{$this->category}-details" )->text();
		}
		$names['template'] = $this->msg( "linter-pager-template-header" )->text();
		return $names;
	}
}

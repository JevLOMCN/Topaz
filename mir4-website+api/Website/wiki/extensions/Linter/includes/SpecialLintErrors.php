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

use HTMLForm;
use MediaWiki\Html\Html;
use MediaWiki\Output\OutputPage;
use MediaWiki\Request\WebRequest;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Title\MalformedTitleException;
use MediaWiki\Title\NamespaceInfo;
use MediaWiki\Title\TitleParser;
use WANObjectCache;

class SpecialLintErrors extends SpecialPage {

	private WANObjectCache $cache;
	private NamespaceInfo $namespaceInfo;
	private TitleParser $titleParser;

	/**
	 * @var string|null
	 */
	private $category;

	/**
	 * @param WANObjectCache $cache
	 * @param NamespaceInfo $namespaceInfo
	 * @param TitleParser $titleParser
	 */
	public function __construct( WANObjectCache $cache, NamespaceInfo $namespaceInfo, TitleParser $titleParser ) {
		parent::__construct( 'LintErrors' );
		$this->cache = $cache;
		$this->namespaceInfo = $namespaceInfo;
		$this->titleParser = $titleParser;
	}

	/**
	 * @param string $titleLabel
	 */
	protected function showFilterForm( $titleLabel ) {
		$selectOptions = [
			(string)$this->msg( 'linter-form-exact-match' )->escaped() => true,
			(string)$this->msg( 'linter-form-prefix-match' )->escaped() => false,
		];
		$namespaces = $this->getContext()->getRequest()->getVal( "wpNamespaceRestrictions" );
		$fields = [
			'NamespaceRestrictions' => [
				'type' => 'namespacesmultiselect',
				'label' => $this->msg( 'linter-form-namespace' )->text(),
				'exists' => true,
				'cssclass' => 'mw-block-partial-restriction',
				'default' => $namespaces,
				'input' => [ 'autocomplete' => false ]
			],
			'titlefield' => [
				'type' => 'title',
				'name' => $titleLabel,
				'label-message' => 'linter-form-title-prefix',
				'exists' => true,
				'required' => false
			],
			'exactmatchradio' => [
				'type' => 'radio',
				'name' => 'exactmatch',
				'options' => $selectOptions,
				'label-message' => 'linter-form-exact-or-prefix',
				'default' => true
			]
		];

		$enableUserInterfaceTagAndTemplateStage =
			$this->getConfig()->get( 'LinterUserInterfaceTagAndTemplateStage' );
		if ( $enableUserInterfaceTagAndTemplateStage ) {
			$selectTemplateOptions = [
				(string)$this->msg( 'linter-form-template-option-all' )->escaped() => 'all',
				(string)$this->msg( 'linter-form-template-option-with' )->escaped() => 'with',
				(string)$this->msg( 'linter-form-template-option-without' )->escaped() => 'without',
			];
			$htmlTags = new HtmlTags( $this );
			$tagAndTemplateFields = [
				'tag' => [
					'type' => 'select',
					'name' => 'tag',
					'label-message' => 'linter-form-tag',
					'options' => $htmlTags->getAllowedHTMLTags()
				],
				'template' => [
					'type' => 'select',
					'name' => 'template',
					'label-message' => 'linter-form-template',
					'options' => $selectTemplateOptions
				]
			];
			$fields = array_merge( $fields, $tagAndTemplateFields );
		}

		$form = HTMLForm::factory( 'ooui', $fields, $this->getContext() );
		$form->setWrapperLegend( true );
		if ( $this->category !== null ) {
			$form->addHeaderHtml( $this->msg( "linter-category-{$this->category}-desc" )->parse() );
		}
		$form->setMethod( 'get' );
		$form->prepareForm()->displayForm( false );
	}

	/**
	 * cleanTitle parses a title and handles a malformed titles, namespaces that are mismatched
	 * and exact title searches that find no matching records, and produce appropriate error messages
	 *
	 * @param string $title
	 * @param array $namespaces
	 * @return array
	 */
	public function cleanTitle( string $title, $namespaces ): array {
		// Check all titles for malformation regardless of exact match or prefix match
		try {
			$titleElements = $this->titleParser->parseTitle( $title );
		} catch ( MalformedTitleException  $e ) {
			return [ 'titlefield' => null, 'error' => 'linter-invalid-title' ];
		}

		// The drop-down namespace defaults to 'all' which is returned as a null, indicating match all namespaces.
		// If 'main' is selected in the drop-down, int 0 is returned. Other namespaces are returned as int values > 0.
		//
		// If the user does not specify a namespace in the title text box, parseTitle sets it to int 0 as the default.
		// If the user entered ':' (main) namespace as the namespace prefix of a title such as ":MyPageTitle",
		// parseTitle will also return int 0 as the namespace. Other valid system namespaces entered as prefixes
		// in the title text box are returned by parseTitle as int values > 0.
		// To determine if the user entered the ':' (main) namespace when int 0 is returned, a separate check for
		// the substring ':' at offset 0 must be performed.

		$titleNamespace = $titleElements->getNamespace();
		// Determine if the user entered ':' (resolves to main) as the namespace part of the title,
		// or was it was set by default by parseTitle() to 0, but the user intended to search across 'all' namespaces.
		if ( $titleNamespace === 0 && $title[0] !== ':' ) {
			$titleNamespace = null;
		}

		if ( $namespaces && $titleNamespace !== null && !in_array( $titleNamespace, $namespaces ) ) {
			// Show the namespace mismatch error if the namespaces specified in drop-down and title text do not match.
			return [ 'titlefield' => null, 'error' => 'linter-namespace-mismatch' ];
		}

		// If no namespaces are selected (null), return the namespace from the title text
		$namespaces = $namespaces ?: [ $titleNamespace ];

		return [ 'titlefield' => $titleElements->getDBkey(), 'namespace' => $namespaces ];
	}

	/**
	 * @param OutputPage $out
	 * @param string|null $message
	 */
	private function displayError( $out, $message ) {
		$out->addHTML(
			Html::element( 'span', [ 'class' => 'error' ],
				$this->msg( $message )->text() )
		);
	}

	/**
	 * Extract namespace settings from the request object,
	 * returning an array of namespace id numbers
	 *
	 * @param WebRequest $request
	 * @return array
	 */
	protected function findNamespaces( $request ) {
		$namespaces = [];
		$activeNamespaces = array_keys(
			$this->namespaceInfo->getCanonicalNamespaces()
		);
		// Remove -2 = "media" and -1 = "Special" namespace elements
		$activeNamespaces = array_filter( $activeNamespaces,
			static function ( $x ) {
				return $x >= 0;
			}
		);
		if ( $request->getCheck( 'wpNamespaceRestrictions' ) ) {
			$namespaceRequestValues = $request->getRawVal( 'wpNamespaceRestrictions' );
			$namespaceIDs = array_map( 'intval', explode( "\n", $namespaceRequestValues ) );
			// Security measure: only allow active namespace IDs to reach the query
			$namespaces = array_values( array_intersect( $activeNamespaces, $namespaceIDs ) );
		}
		return $namespaces;
	}

	/**
	 * @param string|null $par
	 */
	public function execute( $par ) {
		$request = $this->getRequest();
		$out = $this->getOutput();

		$params = $request->getQueryValues();

		$this->setHeaders();
		$this->outputHeader( $par || isset( $params[ 'titlesearch' ] ) ? 'disable-summary' : '' );

		$namespaces = $this->findNamespaces( $request );

		$exactMatch = $request->getBool( 'exactmatch', true );
		$tagName = $this->getRequest()->getText( 'tag' );
		// map command line tag name through an associative array to protect request from an SQL injection security risk
		$htmlTags = new HtmlTags( $this );
		$allowedHtmlTags = $htmlTags->getAllowedHTMLTags();
		$tag = $allowedHtmlTags[ $tagName ] ?? 'all';
		$template = $this->getRequest()->getText( 'template' );

		// If the request contains a 'titlesearch' parameter, then the user entered a page title
		// or just the first few characters of the title. They also may have entered the first few characters
		// of a custom namespace (just the text before a ':') to search for and pressed the associated Submit button.
		// Added the pageback parameter to inform the code that the '<- Special:LintErrors' link had been used to allow
		// the UI to redisplay with previous form values, instead of just resubmitting the query.
		if ( $par === null && isset( $params[ 'titlesearch' ] ) && !isset( $params[ 'pageback'] ) ) {
			unset( $params[ 'title' ] );
			$params = array_merge( [ 'pageback' => true ], $params );
			$out->addBacklinkSubtitle( $this->getPageTitle(), $params );

			$title = $request->getText( 'titlesearch' );
			$titleSearch = $this->cleanTitle( $title, $namespaces );

			if ( $titleSearch[ 'titlefield' ] !== null ) {
				$out->setPageTitleMsg( $this->msg( 'linter-prefix-search-subpage', $titleSearch[ 'titlefield' ] ) );

				$catManager = new CategoryManager();
				$pager = new LintErrorsPager(
					$this->getContext(), null, $this->getLinkRenderer(), $catManager, $namespaces,
					$exactMatch, $titleSearch[ 'titlefield' ], $template, $tag
				);
				$out->addParserOutput( $pager->getFullOutput() );
			} else {
				$this->displayError( $out, $titleSearch[ 'error' ] );
			}
			return;
		}

		$catManager = new CategoryManager();
		if ( in_array( $par, $this->getSubpagesForPrefixSearch() ) ) {
			$this->category = $par;
		}

		if ( !$this->category ) {
			$this->addHelpLink( 'Help:Extension:Linter' );
			$this->showCategoryListings( $catManager );
		} else {
			$this->addHelpLink( "Help:Lint_errors/{$this->category}" );
			$out->setPageTitleMsg(
				$this->msg( 'linterrors-subpage',
					$this->msg( "linter-category-{$this->category}" )->text()
				)
			);
			$out->addBacklinkSubtitle( $this->getPageTitle() );

			$title = $request->getText( 'titlecategorysearch' );
			// For category-based searches, allow an undefined title to display all records
			if ( $title === '' ) {
				$titleCategorySearch = [ 'titlefield' => '', 'namespace' => $namespaces, 'pageid' => null ];
			} else {
				$titleCategorySearch = $this->cleanTitle( $title, $namespaces );
			}

			if ( $titleCategorySearch[ 'titlefield' ] !== null ) {
				$this->showFilterForm( 'titlecategorysearch' );
				$pager = new LintErrorsPager(
					$this->getContext(), $this->category, $this->getLinkRenderer(), $catManager, $namespaces,
					$exactMatch, $titleCategorySearch[ 'titlefield' ], $template, $tag
				);
				$out->addParserOutput( $pager->getFullOutput() );
			} else {
				$this->displayError( $out, $titleCategorySearch[ 'error' ] );
			}
		}
	}

	/**
	 * @param string $priority
	 * @param int[] $totals name => count
	 * @param string[] $categories
	 */
	private function displayList( $priority, $totals, array $categories ) {
		$out = $this->getOutput();
		$msgName = 'linter-heading-' . $priority . '-priority';
		$out->addHTML( Html::element( 'h2', [], $this->msg( $msgName )->text() ) );
		$out->addHTML( $this->buildCategoryList( $categories, $totals ) );
	}

	/**
	 */
	private function displaySearchPage() {
		$out = $this->getOutput();
		$out->addHTML( Html::element( 'h2', [],
			$this->msg( "linter-lints-prefix-search-page-desc" )->text() ) );
		$this->showFilterForm( 'titlesearch' );
	}

	/**
	 * @param CategoryManager $catManager
	 */
	private function showCategoryListings( CategoryManager $catManager ) {
		$lookup = new TotalsLookup(
			$catManager,
			$this->cache,
			new Database( 0 )
		);
		$totals = $lookup->getTotals();

		// Display lint issues by priority
		$this->displayList( 'high', $totals, $catManager->getHighPriority() );
		$this->displayList( 'medium', $totals, $catManager->getMediumPriority() );
		$this->displayList( 'low', $totals, $catManager->getLowPriority() );

		$this->displaySearchPage();
	}

	/**
	 * @param string[] $cats
	 * @param int[] $totals name => count
	 * @return string
	 */
	private function buildCategoryList( array $cats, array $totals ) {
		$linkRenderer = $this->getLinkRenderer();
		$html = Html::openElement( 'ul' ) . "\n";
		foreach ( $cats as $cat ) {
			$html .= Html::rawElement( 'li', [], $linkRenderer->makeKnownLink(
				$this->getPageTitle( $cat ),
				$this->msg( "linter-category-$cat" )->text()
			) . ' ' . Html::element( 'bdi', [],
				$this->msg( "linter-numerrors" )->numParams( $totals[$cat] )->text()
			) ) . "\n";
		}
		$html .= Html::closeElement( 'ul' );

		return $html;
	}

	/** @inheritDoc */
	public function getGroupName() {
		return 'maintenance';
	}

	/**
	 * @return string[]
	 */
	protected function getSubpagesForPrefixSearch() {
		$categoryManager = new CategoryManager();
		return array_merge( $categoryManager->getVisibleCategories(), $categoryManager->getInvisibleCategories() );
	}

}

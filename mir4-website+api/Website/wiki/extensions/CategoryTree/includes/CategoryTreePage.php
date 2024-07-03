<?php
/**
 * © 2006 Daniel Kinzler
 *
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
 * @ingroup Extensions
 * @author Daniel Kinzler, brightbyte.de
 */

namespace MediaWiki\Extension\CategoryTree;

use HTMLForm;
use MediaWiki\Html\Html;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Title\Title;
use SearchEngineFactory;
use Wikimedia\Rdbms\IConnectionProvider;

/**
 * Special page for the CategoryTree extension, an AJAX based gadget
 * to display the category structure of a wiki
 */
class CategoryTreePage extends SpecialPage {
	/** @var string */
	public $target = '';

	/** @var IConnectionProvider */
	private $dbProvider;

	/** @var SearchEngineFactory */
	private $searchEngineFactory;

	/** @var CategoryTree */
	public $tree = null;

	/**
	 * @param IConnectionProvider $dbProvider
	 * @param SearchEngineFactory $searchEngineFactory
	 */
	public function __construct(
		IConnectionProvider $dbProvider,
		SearchEngineFactory $searchEngineFactory
	) {
		parent::__construct( 'CategoryTree' );
		$this->dbProvider = $dbProvider;
		$this->searchEngineFactory = $searchEngineFactory;
	}

	/**
	 * @param string $name
	 * @return mixed
	 */
	private function getOption( $name ) {
		if ( $this->tree ) {
			return $this->tree->optionManager->getOption( $name );
		} else {
			return $this->getConfig()->get( 'CategoryTreeDefaultOptions' )[$name];
		}
	}

	/**
	 * Main execution function
	 * @param string|null $par Parameters passed to the page
	 */
	public function execute( $par ) {
		$this->setHeaders();
		$this->addHelpLink( 'Extension:CategoryTree' );
		$request = $this->getRequest();
		if ( $par ) {
			$this->target = trim( $par );
		} else {
			$this->target = trim( $request->getText( 'target' ) );
			if ( $this->target === '' ) {
				$rootcategory = $this->msg( 'rootcategory' );
				if ( $rootcategory->exists() ) {
					$this->target = $rootcategory->text();
				}
			}
		}

		$options = [];
		$config = $this->getConfig();

		# grab all known options from the request. Normalization is done by the CategoryTree class
		$categoryTreeDefaultOptions = $config->get( 'CategoryTreeDefaultOptions' );
		$categoryTreeSpecialPageOptions = $config->get( 'CategoryTreeSpecialPageOptions' );
		foreach ( $categoryTreeDefaultOptions as $option => $default ) {
			if ( isset( $categoryTreeSpecialPageOptions[$option] ) ) {
				$default = $categoryTreeSpecialPageOptions[$option];
			}

			$options[$option] = $request->getVal( $option, $default );
		}

		$this->tree = new CategoryTree( $options, $config, $this->dbProvider, $this->getLinkRenderer() );

		$this->getOutput()->addWikiMsg( 'categorytree-header' );

		$this->executeInputForm();

		if ( $this->target !== '' ) {
			$this->executeCategoryTree();
		}
	}

	/**
	 * Input form for entering a category
	 */
	private function executeInputForm() {
		$namespaces = $this->getRequest()->getRawVal( 'namespaces' );
		// mode may be overriden by namespaces option
		$mode = ( $namespaces === null ? $this->getOption( 'mode' ) : CategoryTreeMode::ALL );
		if ( $mode === CategoryTreeMode::CATEGORIES ) {
			$modeDefault = 'categories';
		} elseif ( $mode === CategoryTreeMode::PAGES ) {
			$modeDefault = 'pages';
		} else {
			$modeDefault = 'all';
		}

		$formDescriptor = [
			'category' => [
				'type' => 'title',
				'name' => 'target',
				'label-message' => 'categorytree-category',
				'namespace' => NS_CATEGORY,
				'default' => str_replace( '_', ' ', $this->target ),
			],

			'mode' => [
				'type' => 'select',
				'name' => 'mode',
				'label-message' => 'categorytree-mode-label',
				'options-messages' => [
					'categorytree-mode-categories' => 'categories',
					'categorytree-mode-pages' => 'pages',
					'categorytree-mode-all' => 'all',
				],
				'default' => $modeDefault,
				'nodata' => true,
			],

			'namespace' => [
				'type' => 'namespaceselect',
				'name' => 'namespaces',
				'label-message' => 'namespace',
				'all' => '',
			],
		];

		HTMLForm::factory( 'ooui', $formDescriptor, $this->getContext() )
			->setWrapperLegendMsg( 'categorytree-legend' )
			->setSubmitTextMsg( 'categorytree-go' )
			->setMethod( 'get' )
			// Strip subpage
			->setTitle( $this->getPageTitle() )
			->prepareForm()
			->displayForm( false );
	}

	/**
	 * Show the category tree
	 */
	private function executeCategoryTree() {
		$output = $this->getOutput();
		CategoryTree::setHeaders( $output );

		$title = CategoryTree::makeTitle( $this->target );
		if ( !$title || !$title->getArticleID() ) {
			$output->addHTML( Html::rawElement( 'div', [ 'class' => 'CategoryTreeNotice' ],
				$this->msg( 'categorytree-not-found' )
					->plaintextParams( $this->target )
					->parse()
			) );
			return;
		}

		$parents = $this->tree->renderParents( $title );
		if ( $parents === '' ) {
			$parents = $this->msg( 'categorytree-no-parent-categories' )->parse();
		}

		$output->addHTML( Html::rawElement( 'div', [ 'class' => 'CategoryTreeParents' ],
			$this->msg( 'categorytree-parents' )->parse() .
			$this->msg( 'colon-separator' )->escaped() .
			$parents
		) );

		$output->addHTML( Html::rawElement( 'div',
			[
				'class' => 'CategoryTreeResult CategoryTreeTag',
				'data-ct-mode' => $this->tree->optionManager->getOption( 'mode' ),
				'data-ct-options' => $this->tree->optionManager->getOptionsAsJsStructure(),
			],
			$this->tree->renderNode( $title, 1 )
		) );
	}

	/**
	 * Return an array of subpages beginning with $search that this special page will accept.
	 *
	 * @param string $search Prefix to search for
	 * @param int $limit Maximum number of results to return (usually 10)
	 * @param int $offset Number of results to skip (usually 0)
	 * @return string[] Matching subpages
	 */
	public function prefixSearchSubpages( $search, $limit, $offset ) {
		$title = Title::newFromText( $search, NS_CATEGORY );
		if ( $title && !$title->inNamespace( NS_CATEGORY ) ) {
			// Someone searching for something like "Wikipedia:Foo"
			$title = Title::makeTitleSafe( NS_CATEGORY, $search );
		}
		if ( !$title ) {
			// No prefix suggestion outside of category namespace
			return [];
		}
		$searchEngine = $this->searchEngineFactory->create();
		$searchEngine->setLimitOffset( $limit, $offset );
		// Autocomplete subpage the same as a normal search, but just for categories
		$searchEngine->setNamespaces( [ NS_CATEGORY ] );
		$result = $searchEngine->defaultPrefixSearch( $search );

		return array_map( static function ( Title $t ) {
			// Remove namespace in search suggestion
			return $t->getText();
		}, $result );
	}

	/**
	 * @inheritDoc
	 */
	protected function getGroupName() {
		return 'pages';
	}

}

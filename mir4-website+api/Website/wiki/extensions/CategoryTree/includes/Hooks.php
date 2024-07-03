<?php
/**
 * © 2006-2008 Daniel Kinzler and others
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

use MediaWiki\Config\Config;
use MediaWiki\Hook\CategoryViewer__doCategoryQueryHook;
use MediaWiki\Hook\CategoryViewer__generateLinkHook;
use MediaWiki\Hook\OutputPageMakeCategoryLinksHook;
use MediaWiki\Hook\ParserFirstCallInitHook;
use MediaWiki\Hook\SkinBuildSidebarHook;
use MediaWiki\Hook\SpecialTrackingCategories__generateCatLinkHook;
use MediaWiki\Hook\SpecialTrackingCategories__preprocessHook;
use MediaWiki\Html\Html;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\Output\OutputPage;
use MediaWiki\Parser\Sanitizer;
use MediaWiki\ResourceLoader as RL;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Title\Title;
use Parser;
use PPFrame;
use RequestContext;
use Skin;
use Wikimedia\Rdbms\IConnectionProvider;
use Wikimedia\Rdbms\IResultWrapper;

/**
 * Hooks for the CategoryTree extension, an AJAX based gadget
 * to display the category structure of a wiki
 *
 * @phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName
 */
class Hooks implements
	SpecialTrackingCategories__preprocessHook,
	SpecialTrackingCategories__generateCatLinkHook,
	SkinBuildSidebarHook,
	ParserFirstCallInitHook,
	OutputPageMakeCategoryLinksHook,
	CategoryViewer__doCategoryQueryHook,
	CategoryViewer__generateLinkHook
{

	/** @var CategoryCache */
	private $categoryCache;

	/** @var Config */
	private $config;

	/** @var IConnectionProvider */
	private IConnectionProvider $dbProvider;

	/** @var LinkRenderer */
	private $linkRenderer;

	/**
	 * @param CategoryCache $categoryCache
	 * @param Config $config
	 * @param IConnectionProvider $dbProvider
	 * @param LinkRenderer $linkRenderer
	 */
	public function __construct(
		CategoryCache $categoryCache,
		Config $config,
		IConnectionProvider $dbProvider,
		LinkRenderer $linkRenderer
	) {
		$this->categoryCache = $categoryCache;
		$this->config = $config;
		$this->dbProvider = $dbProvider;
		$this->linkRenderer = $linkRenderer;
	}

	/**
	 * @param Parser $parser
	 */
	public function onParserFirstCallInit( $parser ) {
		if ( !$this->config->get( 'CategoryTreeAllowTag' ) ) {
			return;
		}
		$parser->setHook( 'categorytree', [ $this, 'parserHook' ] );
		$parser->setFunctionHook( 'categorytree', [ $this, 'parserFunction' ] );
	}

	/**
	 * Entry point for the {{#categorytree}} tag parser function.
	 * This is a wrapper around Hooks::parserHook
	 * @param Parser $parser
	 * @param string ...$params
	 * @return array|string
	 */
	public function parserFunction( Parser $parser, ...$params ) {
		// first user-supplied parameter must be category name
		if ( !$params ) {
			// no category specified, return nothing
			return '';
		}
		$cat = array_shift( $params );

		// build associative arguments from flat parameter list
		$argv = [];
		foreach ( $params as $p ) {
			if ( preg_match( '/^\s*(\S.*?)\s*=\s*(.*?)\s*$/', $p, $m ) ) {
				$k = $m[1];
				// strip any quotes enclosing the value
				$v = preg_replace( '/^"\s*(.*?)\s*"$/', '$1', $m[2] );
			} else {
				$k = trim( $p );
				$v = true;
			}

			$argv[$k] = $v;
		}

		if ( $parser->getOutputType() === Parser::OT_PREPROCESS ) {
			return Html::rawElement( 'categorytree', $argv, $cat );
		} else {
			// now handle just like a <categorytree> tag
			$html = $this->parserHook( $cat, $argv, $parser );
			return [ $html, 'noparse' => true, 'isHTML' => true ];
		}
	}

	/**
	 * Obtain a category sidebar link based on config
	 * @return bool|string of link
	 */
	private function getCategorySidebarBox() {
		if ( !$this->config->get( 'CategoryTreeSidebarRoot' ) ) {
			return false;
		}
		return $this->parserHook(
			$this->config->get( 'CategoryTreeSidebarRoot' ),
			$this->config->get( 'CategoryTreeSidebarOptions' )
		);
	}

	/**
	 * Hook implementation for injecting a category tree into the sidebar.
	 * Only does anything if $wgCategoryTreeSidebarRoot is set to a category name.
	 * @param Skin $skin
	 * @param array &$sidebar
	 */
	public function onSkinBuildSidebar( $skin, &$sidebar ) {
		$html = $this->getCategorySidebarBox();
		if ( $html ) {
			$sidebar['categorytree-portlet'] = [];
			CategoryTree::setHeaders( $skin->getOutput() );
		}
	}

	/**
	 * Hook implementation for injecting a category tree link into the sidebar.
	 * Only does anything if $wgCategoryTreeSidebarRoot is set to a category name.
	 * @param Skin $skin
	 * @param string $portlet
	 * @param string &$html
	 */
	public function onSkinAfterPortlet( $skin, $portlet, &$html ) {
		if ( $portlet === 'categorytree-portlet' ) {
			$box = $this->getCategorySidebarBox();
			if ( $box ) {
				$html .= $box;
			}
		}
	}

	/**
	 * Entry point for the <categorytree> tag parser hook.
	 * This loads CategoryTreeFunctions.php and calls CategoryTree::getTag()
	 * @param string $cat
	 * @param array $argv
	 * @param Parser|null $parser
	 * @param PPFrame|null $frame
	 * @param bool $allowMissing
	 * @return bool|string
	 */
	public function parserHook(
		$cat,
		array $argv,
		Parser $parser = null,
		PPFrame $frame = null,
		$allowMissing = false
	) {
		if ( $parser ) {
			$parserOutput = $parser->getOutput();
			$parserOutput->addModuleStyles( [ 'ext.categoryTree.styles' ] );
			$parserOutput->addModules( [ 'ext.categoryTree' ] );
		}

		$ct = new CategoryTree( $argv, $this->config, $this->dbProvider, $this->linkRenderer );

		$attr = Sanitizer::validateTagAttributes( $argv, 'div' );

		$hideroot = isset( $argv['hideroot'] )
			? OptionManager::decodeBoolean( $argv['hideroot'] ) : null;
		$onlyroot = isset( $argv['onlyroot'] )
			? OptionManager::decodeBoolean( $argv['onlyroot'] ) : null;
		$depthArg = isset( $argv['depth'] ) ? (int)$argv['depth'] : null;

		$depth = $ct->optionManager->capDepth( $depthArg );
		if ( $onlyroot ) {
			$depth = 0;
		}

		return $ct->getTag( $parser, $cat, $hideroot, $attr, $depth, $allowMissing );
	}

	/**
	 * OutputPageMakeCategoryLinks hook, override category links
	 * @param OutputPage $out
	 * @param array $categories
	 * @param array &$links
	 * @return bool
	 */
	public function onOutputPageMakeCategoryLinks( $out, $categories, &$links ) {
		if ( !$this->config->get( 'CategoryTreeHijackPageCategories' ) ) {
			// Not enabled, don't do anything
			return true;
		}

		$options = $this->config->get( 'CategoryTreePageCategoryOptions' );
		foreach ( $categories as $category => $type ) {
			$links[$type][] = $this->parserHook( $category, $options, null, null, true );
		}
		CategoryTree::setHeaders( $out );

		return false;
	}

	/**
	 * Get exported data for the "ext.categoryTree" ResourceLoader module.
	 *
	 * @internal For use in extension.json only.
	 * @param RL\Context $context
	 * @param Config $config
	 * @return array Data to be serialised as data.json
	 */
	public static function getDataForJs( RL\Context $context, Config $config ) {
		// Look, this is pretty bad but CategoryTree is just whacky, it needs to be rewritten
		$optionManager = new OptionManager( $config->get( 'CategoryTreeCategoryPageOptions' ), $config );

		return [
			'defaultCtOptions' => $optionManager->getOptionsAsJsStructure(),
		];
	}

	/**
	 * Hook handler for the SpecialTrackingCategories::preprocess hook
	 * @param SpecialPage $specialPage SpecialTrackingCategories object
	 * @param array $trackingCategories [ 'msg' => LinkTarget, 'cats' => LinkTarget[] ]
	 * @phan-param array<string,array{msg:LinkTarget,cats:LinkTarget[]}> $trackingCategories
	 */
	public function onSpecialTrackingCategories__preprocess(
		$specialPage,
		$trackingCategories
	) {
		$categoryTargets = [];
		foreach ( $trackingCategories as $data ) {
			foreach ( $data['cats'] as $catTitle ) {
				$categoryTargets[] = $catTitle;
			}
		}
		$this->categoryCache->doQuery( $categoryTargets );
	}

	/**
	 * Hook handler for the SpecialTrackingCategories::generateCatLink hook
	 * @param SpecialPage $specialPage SpecialTrackingCategories object
	 * @param LinkTarget $catTitle LinkTarget object of the linked category
	 * @param string &$html Result html
	 */
	public function onSpecialTrackingCategories__generateCatLink( $specialPage,
		$catTitle, &$html
	) {
		$cat = $this->categoryCache->getCategory( $catTitle );

		$html .= CategoryTree::createCountString( $specialPage->getContext(), $cat, 0 );
	}

	/**
	 * @param string $type
	 * @param IResultWrapper $res
	 */
	public function onCategoryViewer__doCategoryQuery( $type, $res ) {
		if ( $type === 'subcat' && $res ) {
			$this->categoryCache->fillFromQuery( $res );
			CategoryTree::setHeaders( RequestContext::getMain()->getOutput() );
		}
	}

	/**
	 * @param string $type
	 * @param Title $title
	 * @param string $html
	 * @param string &$link
	 * @return bool
	 */
	public function onCategoryViewer__generateLink( $type, $title, $html, &$link ) {
		if ( $type !== 'subcat' || $link !== null ) {
			return true;
		}

		$request = RequestContext::getMain()->getRequest();
		if ( $request->getCheck( 'notree' ) ) {
			return true;
		}

		$options = $this->config->get( 'CategoryTreeCategoryPageOptions' );
		$mode = $request->getRawVal( 'mode' );
		if ( $mode !== null ) {
			$options['mode'] = $mode;
		}
		$tree = new CategoryTree( $options, $this->config, $this->dbProvider, $this->linkRenderer );

		$cat = $this->categoryCache->getCategory( $title );

		$link = $tree->renderNodeInfo( $title, $cat );
		return false;
	}
}

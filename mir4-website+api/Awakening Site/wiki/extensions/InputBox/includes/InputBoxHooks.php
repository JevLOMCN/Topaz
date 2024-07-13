<?php
/**
 * Hooks for InputBox extension
 *
 * @file
 * @ingroup Extensions
 */

namespace MediaWiki\Extension\InputBox;

use Article;
use MediaWiki\Actions\ActionEntryPoint;
use MediaWiki\Config\Config;
use MediaWiki\Hook\MediaWikiPerformActionHook;
use MediaWiki\Hook\ParserFirstCallInitHook;
use MediaWiki\Output\OutputPage;
use MediaWiki\Request\WebRequest;
use MediaWiki\SpecialPage\Hook\SpecialPageBeforeExecuteHook;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use Parser;

/**
 * InputBox hooks
 */
class InputBoxHooks implements
	ParserFirstCallInitHook,
	SpecialPageBeforeExecuteHook,
	MediaWikiPerformActionHook
{
	/** @var Config */
	private $config;

	/**
	 * @param Config $config
	 */
	public function __construct(
		Config $config
	) {
		$this->config = $config;
	}

	/**
	 * Initialization
	 * @param Parser $parser
	 */
	public function onParserFirstCallInit( $parser ) {
		// Register the hook with the parser
		$parser->setHook( 'inputbox', [ $this, 'render' ] );
	}

	/**
	 * Prepend prefix to wpNewTitle if necessary
	 * @param SpecialPage $special
	 * @param string $subPage
	 */
	public function onSpecialPageBeforeExecute( $special, $subPage ) {
		$request = $special->getRequest();
		$prefix = $request->getText( 'prefix', '' );
		$title = $request->getText( 'wpNewTitle', '' );
		$search = $request->getText( 'search', '' );
		$searchfilter = $request->getText( 'searchfilter', '' );
		if ( $special->getName() === 'Movepage' && $prefix !== '' && $title !== '' ) {
			$request->setVal( 'wpNewTitle', $prefix . $title );
			$request->unsetVal( 'prefix' );
		}
		if ( $special->getName() === 'Search' && $searchfilter !== '' ) {
			$request->setVal( 'search', $search . ' ' . $searchfilter );
		}
	}

	/**
	 * Render the input box
	 * @param string $input
	 * @param array $args
	 * @param Parser $parser
	 * @return string
	 */
	public function render( $input, $args, Parser $parser ) {
		// Create InputBox
		$inputBox = new InputBox( $this->config, $parser );

		// Configure InputBox
		$inputBox->extractOptions( $parser->replaceVariables( $input ) );

		// Return output
		return $inputBox->render();
	}

	/**
	 * <inputbox type=create...> sends requests with action=edit, and
	 * possibly a &prefix=Foo.  So we pick that up here, munge prefix
	 * and title together, and redirect back out to the real page
	 * @param OutputPage $output
	 * @param Article $article
	 * @param Title $title
	 * @param User $user
	 * @param WebRequest $request
	 * @param ActionEntryPoint $wiki
	 * @return bool
	 */
	public function onMediaWikiPerformAction(
		$output,
		$article,
		$title,
		$user,
		$request,
		$wiki
	) {
		// In order to check for 'action=edit' in URL parameters, even if another extension overrides
		// the action, we must not use getActionName() here. (T337436)
		if ( $request->getRawVal( 'action' ) !== 'edit' && $request->getRawVal( 'veaction' ) !== 'edit' ) {
			// not our problem
			return true;
		}
		$prefix = $request->getText( 'prefix', '' );
		if ( $prefix === '' ) {
			// Fine
			return true;
		}

		$title = $prefix . $request->getText( 'title', '' );
		$params = $request->getValues();
		unset( $params['prefix'] );
		$params['title'] = $title;

		$output->redirect( wfAppendQuery( $output->getConfig()->get( 'Script' ), $params ), '301' );
		return false;
	}
}

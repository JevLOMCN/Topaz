<?php

namespace MediaWiki\Extension\Math;

use Exception;
use InvalidArgumentException;
use MediaWiki\Extension\Math\Widget\WikibaseEntitySelector;
use MediaWiki\Html\Html;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use MediaWiki\Output\OutputPage;
use MediaWiki\SpecialPage\SpecialPage;
use Message;
use OOUI\ButtonInputWidget;
use OOUI\FormLayout;

class SpecialMathWikibase extends SpecialPage {
	/**
	 * The parameter for this special page
	 */
	private const PARAMETER = "qid";

	/**
	 * @var MathWikibaseConnector Wikibase connection
	 */
	private $wikibase;

	/**
	 * @var \Psr\Log\LoggerInterface
	 */
	private $logger;

	public function __construct() {
		parent::__construct( 'MathWikibase' );

		$this->logger = LoggerFactory::getInstance( 'Math' );
	}

	/**
	 * @inheritDoc
	 */
	public function execute( $par ) {
		global $wgLanguageCode;

		$this->wikibase = MediaWikiServices::getInstance()->get( 'Math.WikibaseConnector' );

		$request = $this->getRequest();
		$output = $this->getOutput();
		$output->enableOOUI();

		$this->setHeaders();
		$output->addModules( [ 'mw.widgets.MathWbEntitySelector' ] );

		$output->setPageTitle(
			$this->getPlainText( 'math-wikibase-header' )
		);

		// Get request
		$requestId = $request->getText( self::PARAMETER, $par );

		// if there is no id requested, show the request form
		if ( !$requestId ) {
			$this->showForm();
		} else {
			$this->logger->debug( "Request qID: " . $requestId );
			try {
				$info = $this->wikibase->fetchWikibaseFromId( $requestId, $wgLanguageCode );
				$this->logger->debug( "Successfully fetched information for qID: " . $requestId );
				$this->buildPageRepresentation( $info, $requestId, $output );
			} catch ( Exception $e ) {
				$this->showError( $e );
			}
		}
	}

	/**
	 * Shows the form to request information for a specific Wikibase id
	 */
	private function showForm() {
		$actionField = new \OOUI\ActionFieldLayout(
			new WikibaseEntitySelector( [
				'name' => self::PARAMETER,
				'placeholder' => $this->getPlainText( 'math-wikibase-special-form-placeholder' ),
				'required' => true,
				'infusable' => true,
				'id' => 'wbEntitySelector'
			] ),
			new ButtonInputWidget( [
				'name' => 'request-qid',
				'label' => $this->getPlainText( 'math-wikibase-special-form-button' ),
				'type' => 'submit',
				'flags' => [ 'primary', 'progressive' ],
				'icon' => 'check',
			] ),
			[
				'label' => $this->getPlainText( 'math-wikibase-special-form-header' ),
				'align' => 'top'
			]
		);

		$formLayout = new FormLayout( [
			'method' => 'POST',
			'items' => [ $actionField ]
		] );

		$this->getOutput()->addHTML( $formLayout );
	}

	/**
	 * Shows an error message for the user and writes information to $logger
	 * @param Exception $e can potentially be any exception.
	 */
	private function showError( Exception $e ) {
		$this->getOutput()->setPageTitle(
			$this->getPlainText( 'math-wikibase-special-error-header' )
		);

		if ( $e instanceof InvalidArgumentException ) {
			$this->logger->warning( "An invalid ID was specified. Reason: " . $e->getMessage() );
			$this->getOutput()->addHTML(
				$this->msg( 'math-wikibase-special-error-invalid-argument' )->inContentLanguage()->parse()
			);
		} else {
			$this->logger->error( "An unknown error occurred while fetching data from Wikibase.", [
				'exception' => $e
			] );
			$this->getOutput()->addHTML(
				$this->msg( 'math-wikibase-special-error-unknown' )->inContentLanguage()->parse()
			);
		}
	}

	/**
	 * Helper function to shorten i18n text processing
	 * @param string $key
	 * @return string the plain text in current content language
	 */
	private function getPlainText( $key ) {
		return $this->msg( $key )->inContentLanguage()->plain();
	}

	/**
	 * @param MathWikibaseInfo $info
	 * @param string $qid
	 * @param OutputPage $output
	 */
	public function buildPageRepresentation(
		MathWikibaseInfo $info,
		$qid,
		OutputPage $output
	) {
		$output->setPageTitle( $info->getLabel() );

		// if 'instance of' is specified, it can be found in the description before a colon
		// FIXME: There are other reasons to have a colon in an Item's description, e.g.
		// https://www.wikidata.org/wiki/Special:MathWikibase?qid=Q6203
		if ( preg_match( '/(.*):\s*(.*)/', $info->getDescription(), $matches ) ) {
			$output->setSubtitle( $matches[1] );
		}

		// add formula information
		$header = $this->msg( 'math-wikibase-formula-information' )->inContentLanguage()
			->plain();
		$output->addHTML( self::createHTMLHeader( $header ) );

		if ( $info->getFormattedSymbol() ) {
			$math = $info->getFormattedSymbol();
			$formulaInfo = new Message( 'math-wikibase-formula-header-format' );
			$formulaInfo->rawParams(
				$this->msg( 'math-wikibase-formula' )->inContentLanguage(),
				$math
			);
			$output->addHTML( Html::rawElement( "p", [], $formulaInfo->inContentLanguage()->parse() ) );
		}

		$labelName = $this->msg(
			'math-wikibase-formula-header-format',
			$this->msg( 'math-wikibase-formula-name' )->inContentLanguage(),
			$info->getLabel()
		)->inContentLanguage()->parse();
		$output->addHTML( Html::rawElement( "p", [], $labelName ) );

		if ( isset( $matches[2] ) ) {
			$labelType = $this->msg(
				'math-wikibase-formula-header-format',
				$this->msg( 'math-wikibase-formula-type' )->inContentLanguage(),
				$matches[1]
			)->inContentLanguage()->parse();
			$output->addHTML( Html::rawElement( "p", [], $labelType ) );

			$description = $matches[2];
		} else {
			$description = $info->getDescription();
		}
		$labelDesc = $this->msg(
			'math-wikibase-formula-header-format',
			$this->msg( 'math-wikibase-formula-description' )->inContentLanguage(),
			$description
		)->inContentLanguage()->parse();
		$output->addHTML( Html::rawElement( "p", [], $labelDesc ) );

		// add parts of formula
		if ( $info->hasParts() ) {
			$elementsHeader = $this->msg( 'math-wikibase-formula-elements-header' )
				->inContentLanguage()->plain();
			$output->addHTML( self::createHTMLHeader( $elementsHeader ) );
			$output->addHTML( $info->generateTableOfParts() );
		}

		// add link information
		$wikibaseHeader = $this->msg(
			'math-wikibase-formula-link-header',
			$info->getDescription()
		)->inContentLanguage()->plain();

		$output->addHTML( self::createHTMLHeader( $wikibaseHeader ) );

		$url = $this->wikibase->buildURL( $qid );
		$link = Html::linkButton( $url, [ "href" => $url ] );
		$output->addHTML( Html::rawElement( "p", [], $link ) );
	}

	/**
	 * @param string $header Plain text
	 * @return string Raw HTML
	 */
	private static function createHTMLHeader( string $header ): string {
		return Html::element(
			'h2',
			[],
			$header
		);
	}
}

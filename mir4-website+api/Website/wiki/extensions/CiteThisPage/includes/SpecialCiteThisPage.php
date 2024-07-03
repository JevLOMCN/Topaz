<?php

namespace MediaWiki\Extension\CiteThisPage;

use HTMLForm;
use MediaWiki\Html\Html;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\SpecialPage\FormSpecialPage;
use MediaWiki\Title\Title;
use Parser;
use ParserFactory;
use ParserOptions;
use SearchEngineFactory;

class SpecialCiteThisPage extends FormSpecialPage {

	/**
	 * @var Parser
	 */
	private $citationParser;

	/**
	 * @var Title|bool
	 */
	protected $title = false;

	/** @var SearchEngineFactory */
	private $searchEngineFactory;

	/** @var RevisionLookup */
	private $revisionLookup;

	/** @var ParserFactory */
	private $parserFactory;

	/**
	 * @param SearchEngineFactory $searchEngineFactory
	 * @param RevisionLookup $revisionLookup
	 * @param ParserFactory $parserFactory
	 */
	public function __construct(
		SearchEngineFactory $searchEngineFactory,
		RevisionLookup $revisionLookup,
		ParserFactory $parserFactory
	) {
		parent::__construct( 'CiteThisPage' );
		$this->searchEngineFactory = $searchEngineFactory;
		$this->revisionLookup = $revisionLookup;
		$this->parserFactory = $parserFactory;
	}

	/**
	 * @param string $par
	 */
	public function execute( $par ) {
		$this->setHeaders();
		$this->addHelpLink( 'Extension:CiteThisPage' );
		parent::execute( $par );
		if ( $this->title instanceof Title ) {
			$id = $this->getRequest()->getInt( 'id' );
			$this->showCitations( $this->title, $id );
		}
	}

	/**
	 * @param HTMLForm $form
	 */
	protected function alterForm( HTMLForm $form ) {
		$form->setMethod( 'get' );
		$form->setFormIdentifier( 'titleform' );
	}

	/**
	 * @return array
	 */
	protected function getFormFields() {
		return [
			'page' => [
				'name' => 'page',
				'type' => 'title',
				'exists' => true,
				'default' => $this->par ?? '',
				'label-message' => 'citethispage-change-target'
			]
		];
	}

	/**
	 * @param array $data
	 * @return bool
	 */
	public function onSubmit( array $data ) {
		// GET forms are "submitted" on every view, so check
		// that some data was put in for page
		if ( strlen( $data['page'] ) ) {
			$this->title = Title::newFromText( $data['page'] );
		}
		return true;
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
		return $this->prefixSearchString( $search, $limit, $offset, $this->searchEngineFactory );
	}

	/** @inheritDoc */
	protected function getGroupName() {
		return 'pagetools';
	}

	/**
	 * @param Title $title
	 * @param int $revId
	 */
	private function showCitations( Title $title, $revId ) {
		if ( !$revId ) {
			$revId = $title->getLatestRevID();
		}

		$out = $this->getOutput();

		$revTimestamp = $this->revisionLookup->getTimestampFromId( $revId );

		if ( !$revTimestamp ) {
			$out->addHTML(
				Html::errorBox(
					$out->msg( 'citethispage-badrevision', $title->getPrefixedText(), $revId )->parse()
				)
			);
			return;
		}

		$parserOptions = $this->getParserOptions();
		// Set the overall timestamp to the revision's timestamp
		$parserOptions->setTimestamp( $revTimestamp );

		$parser = $this->parserFactory->create();
		// Register our <citation> tag which just parses using a different
		// context
		$parser->setHook( 'citation', [ $this, 'citationTag' ] );

		// Also hold on to a separate Parser instance for <citation> tag parsing
		// since we can't parse in a parse using the same Parser
		$this->citationParser = $this->parserFactory->create();

		$ret = $parser->parse(
			$this->getContentText(),
			$title,
			$parserOptions,
			/* $linestart = */ false,
			/* $clearstate = */ true,
			$revId
		);

		$this->getOutput()->addModuleStyles( 'ext.citeThisPage' );
		$this->getOutput()->addParserOutputContent( $ret, [
			'enableSectionEditLinks' => false,
		] );
	}

	/**
	 * Get the content to parse
	 *
	 * @return string
	 */
	private function getContentText() {
		$msg = $this->msg( 'citethispage-content' )->inContentLanguage()->plain();
		if ( $msg == '' ) {
			# With MediaWiki 1.20 the plain text files were deleted
			# and the text moved into SpecialCite.i18n.php
			# This code is kept for b/c in case an installation has its own file "citethispage-content-xx"
			# for a previously not supported language.
			$dir = __DIR__ . '/../';
			$contentLang = $this->getContentLanguage();
			$code = $contentLang->lc( $contentLang->getCode() );
			if ( file_exists( "{$dir}citethispage-content-$code" ) ) {
				$msg = file_get_contents( "{$dir}citethispage-content-$code" );
			} elseif ( file_exists( "{$dir}citethispage-content" ) ) {
				$msg = file_get_contents( "{$dir}citethispage-content" );
			}
		}

		return $msg;
	}

	/**
	 * Get the common ParserOptions for both parses
	 *
	 * @return ParserOptions
	 */
	private function getParserOptions() {
		$parserOptions = ParserOptions::newFromContext( $this->getContext() );
		$parserOptions->setDateFormat( 'default' );
		$parserOptions->setInterfaceMessage( true );
		return $parserOptions;
	}

	/**
	 * Implements the <citation> tag.
	 *
	 * This is a hack to allow content that is typically parsed
	 * using the page's timestamp/pagetitle to use the current
	 * request's time and title
	 *
	 * @param string $text
	 * @param array $params
	 * @param Parser $parser
	 * @return string
	 */
	public function citationTag( $text, $params, Parser $parser ) {
		$parserOptions = $this->getParserOptions();

		$ret = $this->citationParser->parse(
			$text,
			$this->getPageTitle(),
			$parserOptions,
			/* $linestart = */ false
		);

		return Parser::stripOuterParagraph( $ret->getText( [
			'enableSectionEditLinks' => false,
			// This will be inserted into the output of another parser, so there will actually be a wrapper
			'unwrap' => true,
			'wrapperDivClass' => '',
		] ) );
	}

	/** @inheritDoc */
	protected function getDisplayFormat() {
		return 'ooui';
	}

	/** @inheritDoc */
	public function requiresUnblock() {
		return false;
	}

	/** @inheritDoc */
	public function requiresWrite() {
		return false;
	}
}

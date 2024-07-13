<?php

namespace MediaWiki\Extension\Scribunto;

use CodeContentHandler;
use Content;
use ExtensionRegistry;
use MediaWiki\Content\Renderer\ContentParseParams;
use MediaWiki\Content\ValidationParams;
use MediaWiki\Html\Html;
use MediaWiki\MediaWikiServices;
use MediaWiki\Page\PageIdentity;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Status\Status;
use MediaWiki\SyntaxHighlight\SyntaxHighlight;
use MediaWiki\Title\Title;
use TextContent;

/**
 * Scribunto Content Handler
 *
 * @file
 * @ingroup Extensions
 * @ingroup Scribunto
 *
 * @author Brad Jorsch <bjorsch@wikimedia.org>
 */

class ScribuntoContentHandler extends CodeContentHandler {

	/**
	 * @param string $modelId
	 * @param string[] $formats
	 */
	public function __construct(
		$modelId = CONTENT_MODEL_SCRIBUNTO, $formats = [ CONTENT_FORMAT_TEXT ]
	) {
		parent::__construct( $modelId, $formats );
	}

	/**
	 * @return string Class name
	 */
	protected function getContentClass() {
		return ScribuntoContent::class;
	}

	/**
	 * @param string $format
	 * @return bool
	 */
	public function isSupportedFormat( $format ) {
		// An error in an earlier version of Scribunto means we might see this.
		if ( $format === 'CONTENT_FORMAT_TEXT' ) {
			$format = CONTENT_FORMAT_TEXT;
		}
		return parent::isSupportedFormat( $format );
	}

	/**
	 * Only allow this content handler to be used in the Module namespace
	 * @param Title $title
	 * @return bool
	 */
	public function canBeUsedOn( Title $title ) {
		if ( $title->getNamespace() !== NS_MODULE ) {
			return false;
		}

		return parent::canBeUsedOn( $title );
	}

	/** @inheritDoc */
	public function supportsPreloadContent(): bool {
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function validateSave(
		Content $content,
		ValidationParams $validationParams
	) {
		'@phan-var ScribuntoContent $content';
		return $this->validate( $content, $validationParams->getPageIdentity() );
	}

	/**
	 * Checks whether the script is valid
	 *
	 * @param TextContent $content
	 * @param PageIdentity $page
	 * @return Status
	 */
	public function validate( TextContent $content, PageIdentity $page ) {
		if ( !( $page instanceof Title ) ) {
			$titleFactory = MediaWikiServices::getInstance()->getTitleFactory();
			$page = $titleFactory->newFromPageIdentity( $page );
		}

		$engine = Scribunto::newDefaultEngine();
		$engine->setTitle( $page );
		return $engine->validate( $content->getText(), $page->getPrefixedDBkey() );
	}

	/**
	 * @inheritDoc
	 */
	protected function fillParserOutput(
		Content $content,
		ContentParseParams $cpoParams,
		ParserOutput &$parserOutput
	) {
		'@phan-var ScribuntoContent $content';
		$page = $cpoParams->getPage();
		$title = Title::newFromPageReference( $page );
		$parserOptions = $cpoParams->getParserOptions();
		$revId = $cpoParams->getRevId();
		$generateHtml = $cpoParams->getGenerateHtml();
		$parser = MediaWikiServices::getInstance()->getParserFactory()->getInstance();
		$sourceCode = $content->getText();
		$docTitle = Scribunto::getDocPage( $title );
		$docMsg = $docTitle ? wfMessage(
			$docTitle->exists() ? 'scribunto-doc-page-show' : 'scribunto-doc-page-does-not-exist',
			$docTitle->getPrefixedText()
		)->inContentLanguage() : null;

		// Accumulate the following output:
		// - docs (if any)
		// - validation error (if any)
		// - highlighted source code
		$html = '';

		if ( $docMsg && !$docMsg->isDisabled() ) {
			// In order to allow the doc page to categorize the Module page,
			// we need access to the ParserOutput of the doc page.
			// This is why we can't simply use $docMsg->parse().
			//
			// We also can't use use ParserOutput::getText and ParserOutput::collectMetadata
			// to merge the result into $parserOutput, because doing so would remove the
			// ability for Skin/OutputPage to (post-cache) decide on the ParserOutput::getText
			// parameters edit section links, TOC, and user language etc.
			//
			// So instead, this uses the doc page's ParserOutput as the actual ParserOutput
			// we return, and add the other stuff to it. This is the only way to leave
			// skin-decisions undecided and in-tact.
			if ( $parserOptions->getTargetLanguage() === null ) {
				$parserOptions->setTargetLanguage( $docTitle->getPageLanguage() );
			}
			$parserOutput = $parser->parse( $docMsg->plain(), $page, $parserOptions, true, true, $revId );

			// Code is displayed and syntax highlighted as LTR, but the
			// documentation can be RTL on RTL-language wikis.
			//
			// As long as we leave the $parserOutput in-tact, it will preserve the appropiate
			// lang, dir, and class attributes (mw-content-ltr or mw-content-rtl) as needed
			// for correct styling and accessiblity of the documentation page content.
			// These will be applied when OutputPage eventually calls ParserOutput::getText()
			$html .= $parserOutput->getRawText();
		} else {
			$parserOutput = new ParserOutput();
		}

		if ( $docTitle ) {
			// Mark the doc page as transcluded, so that edits to the doc page will
			// purge this Module page.
			$parserOutput->addTemplate( $docTitle, $docTitle->getArticleID(), $docTitle->getLatestRevID() );
		}

		// Validate the script, and include an error message and tracking
		// category if it's invalid
		$status = $this->validate( $content, $title );
		if ( !$status->isOK() ) {
			// FIXME: This uses a Status object, which in turn uses global RequestContext
			// to localize the message. This would poison the ParserCache.
			//
			// But, this code is almost unreachable in practice because there has
			// been no way to create a Module page with invalid content since 2014
			// (we validate and abort on edit, undelete, content-model change etc.).
			// See also T304381.
			$html .= Html::rawElement( 'div', [ 'class' => 'errorbox' ],
				$status->getHTML( 'scribunto-error-short', 'scribunto-error-long' )
			);
			$trackingCategories = MediaWikiServices::getInstance()->getTrackingCategories();
			$trackingCategories->addTrackingCategory( $parserOutput, 'scribunto-module-with-errors-category', $page );
		}

		if ( !$generateHtml ) {
			// The doc page and validation error produce metadata and must happen
			// unconditionally. The next step (syntax highlight) can be skipped if
			// we don't actually need the HTML.
			$parserOutput->setRawText( '' );
			return;
		}

		$engine = Scribunto::newDefaultEngine();
		$engine->setTitle( $title );
		$codeLang = $engine->getGeSHiLanguage();
		$html .= $this->highlight( $sourceCode, $parserOutput, $codeLang );

		$parserOutput->setRawText( $html );
	}

	/**
	 * Get syntax highlighted code and add metadata to output.
	 *
	 * If SyntaxHighlight is not possible, falls back to a `<pre>` element.
	 *
	 * @param string $source Source code
	 * @param ParserOutput $parserOutput
	 * @param string|false $codeLang
	 * @return string HTML
	 */
	private function highlight( $source, ParserOutput $parserOutput, $codeLang ) {
		$useGeSHi = MediaWikiServices::getInstance()->getMainConfig()->get( 'ScribuntoUseGeSHi' );
		if (
			$useGeSHi && $codeLang && ExtensionRegistry::getInstance()->isLoaded( 'SyntaxHighlight' )
		) {
			$status = SyntaxHighlight::highlight( $source, $codeLang, [ 'line' => true, 'linelinks' => 'L' ] );
			if ( $status->isGood() ) {
				// @todo replace addModuleStyles line with the appropriate call on
				// SyntaxHighlight once one is created
				$parserOutput->addModuleStyles( [ 'ext.pygments' ] );
				$parserOutput->addModules( [ 'ext.pygments.linenumbers' ] );
				return $status->getValue();
			}
		}

		return Html::element( 'pre', [
			// Same as CodeContentHandler
			'lang' => 'en',
			'dir' => 'ltr',
			'class' => 'mw-code mw-script'
		], "\n$source\n" );
	}

	/**
	 * Create a redirect version of the content
	 *
	 * @param Title $target
	 * @param string $text
	 * @return ScribuntoContent
	 */
	public function makeRedirectContent( Title $target, $text = '' ) {
		return Scribunto::newDefaultEngine()->makeRedirectContent( $target, $text );
	}

	/**
	 * @return bool
	 */
	public function supportsRedirects() {
		return Scribunto::newDefaultEngine()->supportsRedirects();
	}
}

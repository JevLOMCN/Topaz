<?php
/**
 * DiscussionTools parser hooks
 *
 * @file
 * @ingroup Extensions
 * @license MIT
 */

namespace MediaWiki\Extension\DiscussionTools\Hooks;

use MediaWiki\Config\Config;
use MediaWiki\Config\ConfigFactory;
use MediaWiki\Extension\DiscussionTools\CommentFormatter;
use MediaWiki\Hook\GetDoubleUnderscoreIDsHook;
use MediaWiki\Hook\ParserAfterTidyHook;
use MediaWiki\Hook\ParserOutputPostCacheTransformHook;
use MediaWiki\Parser\ParserOutputFlags;
use MediaWiki\Parser\Parsoid\PageBundleParserOutputConverter;
use MediaWiki\Parser\Parsoid\ParsoidParser;
use MediaWiki\Title\Title;
use Parser;
use ParserOutput;

class ParserHooks implements
	ParserOutputPostCacheTransformHook,
	GetDoubleUnderscoreIDsHook,
	ParserAfterTidyHook
{

	private Config $config;

	public function __construct(
		ConfigFactory $configFactory
	) {
		$this->config = $configFactory->makeConfig( 'discussiontools' );
	}

	private function transformHtml(
		ParserOutput $pout, string &$html, Title $title, bool $isPreview
	): void {
		// This condition must be unreliant on current enablement config or user preference.
		// In other words, include parser output of talk pages with DT disabled.
		//
		// This is similar to HookUtils::isAvailableForTitle, but instead of querying the
		// database for the latest metadata of a page that exists, we check metadata of
		// the given ParserOutput object only (this runs before the edit is saved).
		if ( $title->isTalkPage() || $pout->getNewSection() ) {
			$talkExpiry = $this->config->get( 'DiscussionToolsTalkPageParserCacheExpiry' );
			// Override parser cache expiry of talk pages (T280605).
			// Note, this can only shorten it. MediaWiki ignores values higher than the default.
			// NOTE: this currently has no effect for Parsoid read
			// views, since parsoid executes this method as a
			// post-cache transform.  *However* future work may allow
			// caching of intermediate results of the "post cache"
			// transformation pipeline, in which case this code will
			// again be effective. (More: T350626)
			if ( $talkExpiry > 0 ) {
				$pout->updateCacheExpiry( $talkExpiry );
			}
		}

		// Always apply the DOM transform if DiscussionTools are available for this page,
		// to allow linking to individual comments from Echo 'mention' and 'edit-user-talk'
		// notifications (T253082, T281590), and to reduce parser cache fragmentation (T279864).
		// The extra buttons are hidden in CSS (ext.discussionTools.init.styles module) when
		// the user doesn't have DiscussionTools features enabled.
		if ( HookUtils::isAvailableForTitle( $title ) ) {
			// This modifies $html
			CommentFormatter::addDiscussionTools( $html, $pout, $title );

			if ( $isPreview ) {
				$html = CommentFormatter::removeInteractiveTools( $html );
				// Suppress the empty state
				$pout->setExtensionData( 'DiscussionTools-isEmptyTalkPage', null );
			}

			$pout->addModuleStyles( [ 'ext.discussionTools.init.styles' ] );
		}
	}

	/**
	 * For now, this hook only runs on Parsoid HTML. Eventually, this is likely
	 * to be run for legacy HTML but that requires ParserCache storage to be allocated
	 * for DiscussionTools HTML which will be purused separately.
	 *
	 * @inheritDoc
	 */
	public function onParserOutputPostCacheTransform( $parserOutput, &$text, &$options ): void {
		$isPreview = $parserOutput->getOutputFlag( ParserOutputFlags::IS_PREVIEW );

		// We want to run this hook only on Parsoid HTML for now.
		// (and leave the onParserAfterTidy handler for legacy HTML).
		if ( PageBundleParserOutputConverter::hasPageBundle( $parserOutput ) ) {
			$titleDbKey = $parserOutput->getExtensionData( ParsoidParser::PARSOID_TITLE_KEY );
			$title = Title::newFromDBkey( $titleDbKey );
			'@phan-var Title $title';
			$this->transformHtml( $parserOutput, $text, $title, $isPreview );
		}
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ParserAfterTidy
	 *
	 * @param Parser $parser
	 * @param string &$text
	 */
	public function onParserAfterTidy( $parser, &$text ) {
		$pOpts = $parser->getOptions();
		if ( $pOpts->getInterfaceMessage() ) {
			return;
		}

		$this->transformHtml(
			$parser->getOutput(), $text, $parser->getTitle(), $pOpts->getIsPreview()
		);
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/GetDoubleUnderscoreIDs
	 *
	 * @param string[] &$doubleUnderscoreIDs
	 * @return bool|void
	 */
	public function onGetDoubleUnderscoreIDs( &$doubleUnderscoreIDs ) {
		$doubleUnderscoreIDs[] = 'archivedtalk';
		$doubleUnderscoreIDs[] = 'notalk';
	}
}

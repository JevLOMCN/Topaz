<?php
/**
 * Helper functions for using the REST interface to Parsoid.
 *
 * @file
 * @ingroup Extensions
 * @copyright 2022 VisualEditor Team and others; see AUTHORS.txt
 * @license MIT
 */

namespace MediaWiki\Extension\VisualEditor;

use MediaWiki\MediaWikiServices;
use MediaWiki\Page\PageIdentity;
use MediaWiki\Permissions\Authority;
use MediaWiki\Rest\Handler\Helper\HtmlInputTransformHelper;
use MediaWiki\Rest\Handler\Helper\HtmlOutputRendererHelper;
use MediaWiki\Rest\Handler\Helper\PageRestHelperFactory;
use MediaWiki\Revision\MutableRevisionRecord;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\SlotRecord;
use User;
use Wikimedia\Bcp47Code\Bcp47Code;
use WikitextContent;

class DirectParsoidClient implements ParsoidClient {

	/**
	 * Requested Parsoid HTML version.
	 * Keep this in sync with the Accept: header in
	 * ve.init.mw.ArticleTargetLoader.js
	 */
	public const PARSOID_VERSION = '2.8.0';

	private const FLAVOR_DEFAULT = 'view';

	/** @var PageRestHelperFactory */
	private $helperFactory;

	/** @var Authority */
	private $performer;

	/**
	 * @param PageRestHelperFactory $helperFactory
	 * @param Authority $performer
	 */
	public function __construct(
		PageRestHelperFactory $helperFactory,
		Authority $performer
	) {
		$this->performer = $performer;
		$this->helperFactory = $helperFactory;
	}

	/**
	 * @param PageIdentity $page
	 * @param RevisionRecord|null $revision
	 * @param Bcp47Code|null $pageLanguage
	 * @param bool $stash
	 * @param string $flavor
	 *
	 * @return HtmlOutputRendererHelper
	 */
	private function getHtmlOutputRendererHelper(
		PageIdentity $page,
		?RevisionRecord $revision = null,
		Bcp47Code $pageLanguage = null,
		bool $stash = false,
		string $flavor = self::FLAVOR_DEFAULT
	): HtmlOutputRendererHelper {
		$helper = $this->helperFactory->newHtmlOutputRendererHelper();

		// TODO: remove this once we no longer need a User object for rate limiting (T310476).
		if ( $this->performer instanceof User ) {
			$user = $this->performer;
		} else {
			$user = User::newFromIdentity( $this->performer->getUser() );
		}

		$helper->init( $page, [], $user, $revision );

		// Ensure we get a compatible version, not just the default
		$helper->setOutputProfileVersion( self::PARSOID_VERSION );

		$helper->setStashingEnabled( $stash );
		if ( !$stash ) {
			$helper->setFlavor( $flavor );
		}

		if ( $revision ) {
			$helper->setRevision( $revision );
		}

		if ( $pageLanguage ) {
			$helper->setPageLanguage( $pageLanguage );
		}

		return $helper;
	}

	/**
	 * @param PageIdentity $page
	 * @param string $html
	 * @param int|null $oldid
	 * @param string|null $etag
	 * @param Bcp47Code|null $pageLanguage
	 *
	 * @return HtmlInputTransformHelper
	 */
	private function getHtmlInputTransformHelper(
		PageIdentity $page,
		string $html,
		int $oldid = null,
		string $etag = null,
		Bcp47Code $pageLanguage = null
	): HtmlInputTransformHelper {
		$helper = $this->helperFactory->newHtmlInputTransformHelper();

		// Fake REST body
		$body = [
			'html' => [
				'body' => $html,
			]
		];

		$metrics = MediaWikiServices::getInstance()->getParsoidSiteConfig()->metrics();
		if ( $metrics ) {
			$helper->setMetrics( $metrics );
		}

		if ( $oldid || $etag ) {
			$body['original']['revid'] = $oldid;
			$body['original']['renderid'] = $etag;
		}

		$helper->init( $page, $body, [], null, $pageLanguage );

		return $helper;
	}

	/**
	 * Request page HTML from Parsoid.
	 *
	 * @param RevisionRecord $revision Page revision
	 * @param ?Bcp47Code $targetLanguage Page language (default: `null`)
	 *
	 * @return array An array mimicking a RESTbase server's response, with keys: 'headers' and 'body'
	 * @phan-return array{body:string,headers:array<string,string>}
	 */
	public function getPageHtml( RevisionRecord $revision, ?Bcp47Code $targetLanguage = null ): array {
		// In the VE client, we always want to stash.
		$page = $revision->getPage();

		$helper = $this->getHtmlOutputRendererHelper( $page, $revision, $targetLanguage, true );
		$parserOutput = $helper->getHtml();

		return $this->fakeRESTbaseHTMLResponse( $parserOutput->getRawText(), $helper );
	}

	/**
	 * @param PageIdentity $page
	 * @param string $wikitext
	 *
	 * @return RevisionRecord
	 */
	private function makeFakeRevision(
		PageIdentity $page,
		string $wikitext
	): RevisionRecord {
		$rev = new MutableRevisionRecord( $page );
		$rev->setId( 0 );
		$rev->setPageId( $page->getId() );

		$rev->setContent( SlotRecord::MAIN, new WikitextContent( $wikitext ) );

		return $rev;
	}

	/**
	 * Transform wikitext to HTML with Parsoid.
	 *
	 * @param PageIdentity $page The page the content belongs to use as the parsing context
	 * @param Bcp47Code $targetLanguage Page language
	 * @param string $wikitext The wikitext fragment to parse
	 * @param bool $bodyOnly Whether to provide only the contents of the `<body>` tag
	 * @param int|null $oldid What oldid revision, if any, to base the request from (default: `null`)
	 * @param bool $stash Whether to stash the result in the server-side cache (default: `false`)
	 *
	 * @return array An array mimicking a RESTbase server's response, with keys: 'headers' and 'body'
	 * @phan-return array{body:string,headers:array<string,string>}
	 */
	public function transformWikitext(
		PageIdentity $page,
		Bcp47Code $targetLanguage,
		string $wikitext,
		bool $bodyOnly,
		?int $oldid,
		bool $stash
	): array {
		$revision = $this->makeFakeRevision( $page, $wikitext );

		$helper = $this->getHtmlOutputRendererHelper( $page, $revision, $targetLanguage, $stash );

		if ( $bodyOnly ) {
			$helper->setFlavor( 'fragment' );
		}

		$parserOutput = $helper->getHtml();
		$html = $parserOutput->getRawText();

		return $this->fakeRESTbaseHTMLResponse( $html, $helper );
	}

	/**
	 * Transform HTML to wikitext with Parsoid
	 *
	 * @param PageIdentity $page The page the content belongs to
	 * @param Bcp47Code $targetLanguage The desired output language
	 * @param string $html The HTML of the page to be transformed
	 * @param ?int $oldid What oldid revision, if any, to base the request from (default: `null`)
	 * @param ?string $etag The ETag to set in the HTTP request header
	 *
	 * @return array An array mimicking a RESTbase server's response, with keys: 'headers' and 'body'
	 * @phan-return array{body:string,headers:array<string,string>}
	 */
	public function transformHTML(
		PageIdentity $page, Bcp47Code $targetLanguage, string $html, ?int $oldid, ?string $etag
	): array {
		$helper = $this->getHtmlInputTransformHelper( $page, $html, $oldid, $etag, $targetLanguage );

		$content = $helper->getContent();
		$format = $content->getDefaultFormat();

		return [
			'headers' => [
				'Content-Type' => $format,
			],
			'body' => $content->serialize( $format ),
		];
	}

	/**
	 * @param mixed $data
	 * @param HtmlOutputRendererHelper $helper
	 *
	 * @return array
	 */
	private function fakeRESTbaseHTMLResponse( $data, HtmlOutputRendererHelper $helper ): array {
		$contentLanguage = $helper->getHtmlOutputContentLanguage();
		return [
			'headers' => [
				'content-language' => $contentLanguage->toBcp47Code(),
				'etag' => $helper->getETag()
			],
			'body' => $data,
		];
	}

}

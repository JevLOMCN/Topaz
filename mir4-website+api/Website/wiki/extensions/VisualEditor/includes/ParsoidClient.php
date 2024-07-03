<?php
namespace MediaWiki\Extension\VisualEditor;

use MediaWiki\Page\PageIdentity;
use MediaWiki\Revision\RevisionRecord;
use Wikimedia\Bcp47Code\Bcp47Code;

interface ParsoidClient {

	/**
	 * Request page HTML
	 *
	 * @param RevisionRecord $revision Page revision
	 * @param Bcp47Code|null $targetLanguage Desired output language
	 *
	 * @return array An array mimicking a RESTbase server's response, with keys: 'headers' and 'body'
	 * @phan-return array{body:string,headers:array<string,string>}
	 */
	public function getPageHtml( RevisionRecord $revision, ?Bcp47Code $targetLanguage ): array;

	/**
	 * Transform HTML to wikitext via Parsoid
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
		PageIdentity $page,
		Bcp47Code $targetLanguage,
		string $html,
		?int $oldid,
		?string $etag
	): array;

	/**
	 * Transform wikitext to HTML via Parsoid.
	 *
	 * @param PageIdentity $page The page the content belongs to
	 * @param Bcp47Code $targetLanguage The desired output language
	 * @param string $wikitext The wikitext fragment to parse
	 * @param bool $bodyOnly Whether to provide only the contents of the `<body>` tag
	 * @param ?int $oldid What oldid revision, if any, to base the request from (default: `null`)
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
	): array;
}

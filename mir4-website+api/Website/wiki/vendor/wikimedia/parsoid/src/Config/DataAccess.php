<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Config;

use Wikimedia\Parsoid\Core\ContentMetadataCollector;
use Wikimedia\Parsoid\Core\LinkTarget;

/**
 * MediaWiki data access abstract class for Parsoid
 */
abstract class DataAccess {
	/**
	 * Base constructor.
	 *
	 * This constructor is public because it is used to create mock objects
	 * in our test suite.
	 */
	public function __construct() {
	}

	/**
	 * Return target data for formatting links.
	 *
	 * Replaces Batcher.getPageProps()
	 *
	 * @param PageConfig|LinkTarget $pageConfigOrTitle
	 *  Either a PageConfig or else just the context title from the PageConfig
	 *  (as a LinkTarget)
	 * @param string[] $titles
	 * @return array<string,array> [ string Title => array ], where the array contains
	 *  - pageId: (int|null) Page ID
	 *  - revId: (int|null) Current revision of the page
	 *  - missing: (bool) Whether the page is missing
	 *  - known: (bool) Whether the special page is known
	 *  - redirect: (bool) Whether the page is a redirect
	 *  - linkclasses: (string[]) Extensible "link color" information; see
	 *      ApiQueryInfo::getLinkClasses() in MediaWiki core
	 */
	abstract public function getPageInfo( $pageConfigOrTitle, array $titles ): array;

	/**
	 * Return information about files (images)
	 *
	 * This replaces ImageInfoRequest and Batcher.imageinfo()
	 *
	 * @param PageConfig $pageConfig
	 * @param array $files [ [string Name, array Dims] ]. The array may contain
	 *  - width: (int) Requested thumbnail width
	 *  - height: (int) Requested thumbnail height
	 *  - page: (int) Requested thumbnail page number
	 *  - seek: (int) Requested thumbnail time offset
	 * @return array [ array|null ], where the array contains
	 *  - width: (int|false) File width, false if unknown
	 *  - height: (int|false) File height, false if unknown
	 *  - size: (int|false) File size in bytes, false if unknown
	 *  - mediatype: (string) File media type
	 *  - mime: (string) File MIME type
	 *  - url: (string) File URL
	 *  - mustRender: (bool) False if the file can be directly rendered by browsers
	 *  - badFile: (bool) Whether the file is on the "bad image list"
	 *  - duration: (float, optional) Duration of the media in seconds
	 *  - thumberror: (string, optional) Error text if thumbnailing failed. Ugh.
	 *  - responsiveUrls: (string[], optional) Map of display densities to URLs.
	 *  - thumbdata: (mixed, optional) MediaWiki File->getAPIData()
	 *  - thumburl: (string, optional) Thumbnail URL
	 *  - thumbwidth: (int, optional) Thumbnail width
	 *  - thumbheight: (int, optional) Thumbnail height
	 *  - timestamp: (string, optional) Timestamp
	 *  - sha1: (string, optional) SHA-1
	 */
	abstract public function getFileInfo( PageConfig $pageConfig, array $files ): array;

	/**
	 * Perform a pre-save transform on wikitext
	 *
	 * This replaces PHPParseRequest with onlypst = true
	 *
	 * @todo Parsoid should be able to do this itself.
	 * @param PageConfig $pageConfig
	 * @param string $wikitext
	 * @return string Processed wikitext
	 */
	abstract public function doPst( PageConfig $pageConfig, string $wikitext ): string;

	/**
	 * Perform a parse on wikitext
	 *
	 * This replaces PHPParseRequest with onlypst = false, and Batcher.parse()
	 *
	 * @todo Parsoid should be able to do this itself.
	 * @param PageConfig $pageConfig
	 * @param ContentMetadataCollector $metadata Will collect metadata about
	 *   the parsed content.
	 * @param string $wikitext
	 * @return string Output HTML
	 */
	abstract public function parseWikitext(
		PageConfig $pageConfig,
		ContentMetadataCollector $metadata,
		string $wikitext
	): string;

	/**
	 * Preprocess wikitext
	 *
	 * This replaces PreprocessorRequest and Batcher.preprocess()
	 *
	 * @todo Parsoid should be able to do this itself.
	 * @param PageConfig $pageConfig
	 * @param ContentMetadataCollector $metadata Will collect metadata about
	 *   the preprocessed content.
	 * @param string $wikitext
	 * @return string Expanded wikitext
	 */
	abstract public function preprocessWikitext(
		PageConfig $pageConfig,
		ContentMetadataCollector $metadata,
		string $wikitext
	): string;

	/**
	 * Fetch latest revision of article/template content for transclusion.
	 *
	 * Technically, the ParserOptions might select a different
	 * revision other than the latest via
	 * ParserOptions::getTemplateCallback() (used for FlaggedRevisions,
	 * etc), but the point is that template lookups are by title, not
	 * revision id.
	 *
	 * This replaces TemplateRequest
	 *
	 * @todo TemplateRequest also returns a bunch of other data, but seems to never use it except for
	 *   TemplateRequest.setPageSrcInfo() which is replaced by PageConfig.
	 * @param PageConfig $pageConfig
	 * @param LinkTarget $title Title of the page to fetch
	 * @return PageContent|null
	 */
	abstract public function fetchTemplateSource(
		PageConfig $pageConfig, LinkTarget $title
	): ?PageContent;

	/**
	 * Fetch templatedata for a title
	 *
	 * This replaces TemplateDataRequest
	 *
	 * @param PageConfig $pageConfig
	 * @param LinkTarget $title
	 * @return array|null
	 */
	abstract public function fetchTemplateData( PageConfig $pageConfig, LinkTarget $title ): ?array;

	/**
	 * Log linter data.
	 *
	 * @param PageConfig $pageConfig
	 * @param array $lints
	 */
	abstract public function logLinterData( PageConfig $pageConfig, array $lints ): void;
}

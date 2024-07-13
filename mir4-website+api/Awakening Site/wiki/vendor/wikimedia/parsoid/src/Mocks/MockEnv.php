<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Mocks;

use Wikimedia\Parsoid\Config\Env;
use Wikimedia\Parsoid\Config\PageContent;
use Wikimedia\Parsoid\Config\StubMetadataCollector;

class MockEnv extends Env {
	/**
	 * @param array $opts
	 *  - log: (bool) Whether the logger should log. Default false.
	 *  - wrapSections: (bool) Whether to wrap sections. Default false.
	 *  - pageConfig: (PageConfig) If given, supplies a custom PageConfig instance to use.
	 *  - siteConfig: (SiteConfig) If given, supplies a custom SiteConfig instance to use.
	 *  - dataAccess: (DataAccess) If given, supplies a custom DataAccess instance to use.
	 *  - pageContent: (PageContent|string) If given and 'pageConfig' is not, this is passed to the
	 *    MockPageConfig.
	 */
	public function __construct( array $opts ) {
		$siteConfig = $opts['siteConfig'] ?? new MockSiteConfig( $opts );
		if ( isset( $opts['pageConfig'] ) ) {
			$pageConfig = $opts['pageConfig'];
		} else {
			$content = $opts['pageContent'] ?? 'Some dummy source wikitext for testing.';
			$pageContent = $content instanceof PageContent
				? $content
				: new MockPageContent( [ 'main' => $content ] );
			$pageConfig = new MockPageConfig( $siteConfig, $opts, $pageContent );
		}
		$dataAccess = $opts['dataAccess'] ?? new MockDataAccess( $siteConfig, $opts );
		$metadata = new StubMetadataCollector( $siteConfig );
		parent::__construct( $siteConfig, $pageConfig, $dataAccess, $metadata, $opts );
	}

	/**
	 * @suppress PhanEmptyPublicMethod
	 * @param string $resource
	 * @param int $count
	 */
	public function bumpParserResourceUse( string $resource, int $count = 1 ): void {
	}
}

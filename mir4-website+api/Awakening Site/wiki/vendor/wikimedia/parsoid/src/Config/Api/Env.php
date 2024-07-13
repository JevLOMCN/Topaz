<?php

declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Config\Api;

use Wikimedia\Parsoid\Config\Env as IEnv;
use Wikimedia\Parsoid\Config\StubMetadataCollector;

/**
 * An Env accessing MediaWiki via its Action API
 *
 * Note this is intended for testing, not performance.
 */
class Env extends IEnv {

	/**
	 * @param array $opts In addition to those from the parent class,
	 *  - log: (bool) If true, write log data to stderr.
	 *  - apiEndpoint: (string) URL for api.php. Required.
	 *  - title: (string) Page being parsed. Required.
	 *  - apiTimeout: (int) Timeout, in sections. Default 60.
	 *  - userAgent: (string) User agent prefix.
	 */
	public function __construct( array $opts ) {
		$api = new ApiHelper( $opts );

		$siteConfig = new SiteConfig( $api, $opts );
		$pageConfig = new PageConfig( $api, $siteConfig, $opts );
		$dataAccess = new DataAccess( $api, $siteConfig, $opts );
		$metadata = new StubMetadataCollector( $siteConfig );
		parent::__construct( $siteConfig, $pageConfig, $dataAccess, $metadata, $opts );
	}

	// Narrow inherited type; see
	// https://wiki.php.net/rfc/covariant-returns-and-contravariant-parameters

	/** @return SiteConfig */
	public function getSiteConfig(): \Wikimedia\Parsoid\Config\SiteConfig {
		// @phan-suppress-next-line PhanTypeMismatchReturnSuperType
		return parent::getSiteConfig();
	}
}

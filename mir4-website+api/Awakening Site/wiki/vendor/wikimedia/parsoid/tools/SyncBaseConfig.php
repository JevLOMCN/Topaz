<?php

namespace Wikimedia\Parsoid\Tools;

require_once __DIR__ . '/FetchingTool.php';

use Wikimedia\Parsoid\Config\Api\ApiHelper;
use Wikimedia\Parsoid\Config\Api\SiteConfig;

/**
 * Class SyncBaseConfig
 * @package Wikimedia\Parsoid\Tools
 * This tool allows to sync the parsoid/baseconfig files with the latest version running. See
 * README in that directory for more information.
 */

class SyncBaseConfig extends FetchingTool {
	/** Creates supported parameters and description for the syncbaseconfig script and adds the
	 * generic ones
	 */
	public function addDefaultParams(): void {
		$this->addOption( 'prefix',
			'Which wiki prefix to use; e.g. "enwiki" for English wikipedia, "eswiki" ' .
			'for Spanish, "mediawikiwiki" for mediawiki.org' );

		$this->addOption( 'domain', 'Which wiki to use; e.g. "en.wikipedia.org" ' .
			'for English wikipedia, "es.wikipedia.org" for Spanish, ' .
			'"www.mediawiki.org" for mediawiki.org' );

		$this->addOption( 'formatversion',
			'Which formatversion to use for the JSON response format (1 or 2); default is 1' );

		$this->addOption( 'apiURL', 'The API URL of a custom wiki to use (for wikis ' .
			'that are not part of the list provided in wmf.sitematrix.json). The corresponding ' .
			'file is stored as [wikiId].json.' );

		$this->addDescription( 'Rewrites one cached siteinfo configuration.' .
			'Use --domain, --prefix or --apiURL to select which one to rewrite.' );

		parent::addDefaultParams();
	}

	/** Update an individual baseconfig file corresponding to a single format version of a single
	 * prefix/domain
	 */
	public function execute() {
		$this->maybeHelp();

		$dompref = $this->getDomainAndPrefix();
		$apiCall = $this->getApiCall( $dompref );
		$apiHelper = new ApiHelper( $apiCall );

		// API JSON response format version: see https://www.mediawiki.org/wiki/API:JSON_version_2
		$formatVersion =
			$this->hasOption( 'formatversion' ) ? $this->getOption( 'formatversion' ) : "2";

		$apiReqParameters = $this->getApiReqParameters( $formatVersion );
		$response = $apiHelper->makeRequest( $apiReqParameters );

		$fileName = $this->getFileName( $response['query']['general']['wikiid'], $formatVersion );

		$strippedResponse = [
			'query' => $response['query'],
		];
		$resultStr =
			json_encode( $strippedResponse,
				JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		file_put_contents( $fileName, $resultStr );

		print( "Wrote $fileName\n" );
	}

	/** Creates parameters for the API call to get the baseconfig file
	 * @param int $formatversion the desired format version of the JSON response
	 * @return array of parameters for the API call
	 */
	private function getApiReqParameters( int $formatversion ): array {
		$res = SiteConfig::SITE_CONFIG_QUERY_PARAMS;
		$res['formatversion'] = $formatversion;
		$res['format'] = 'json';
		$res['rawcontinue'] = 1;

		return $res;
	}

	/** Creates the full file name from the wikiId and formatVersion
	 * @param string $wikiId wiki identifier (https://www.mediawiki.org/wiki/Manual:Wiki_ID)
	 * @param string $formatVersion either "1" or "2"
	 * @return string the full path of the file where the baseconfig will be stored
	 */
	private function getFileName( string $wikiId, string $formatVersion ): string {
		// HACK for be-tarask
		if ( $wikiId === 'be_x_oldwiki' ) {
			$wikiId = 'be-taraskwiki';
		}

		$configDir = realpath( __DIR__ . "/.." );

		return $configDir . '/baseconfig/' . ( ( $formatVersion !== "2" ) ? "$formatVersion/" : '' ) . $wikiId .
			'.json';
	}
}

$maintClass = SyncBaseConfig::class;
require_once PARSOID_RUN_MAINTENANCE_IF_MAIN;

<?php

namespace Wikimedia\Parsoid\Tools;

use Wikimedia\Parsoid\Config\Api\ApiHelper;

require_once __DIR__ . '/Maintenance.php';

/**
 * Class FetchWmfSitematrix
 * Simple script to update sitematrix.json
 */
class FetchWmfSitematrix extends Maintenance {

	/**
	 * Updates sitematrix.json
	 */
	public function execute() {
		$apiHelperOpts = [
			'apiEndpoint' => 'https://en.wikipedia.org/w/api.php'
		];
		$apiHelper = new ApiHelper( $apiHelperOpts );
		$apiParams = [
			'action' => 'sitematrix',
			'format' => 'json',
			'formatversion' => 1
		];
		$response = $apiHelper->makeRequest( $apiParams );

		$fileName = __DIR__ . '/data/wmf.sitematrix.json';
		$resultStr = json_encode( $response,
			JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		file_put_contents( $fileName, $resultStr );
	}
}

$maintClass = FetchWmfSitematrix::class;
require_once PARSOID_RUN_MAINTENANCE_IF_MAIN;

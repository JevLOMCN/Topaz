<?php

namespace TextExtracts;

use ApiBase;
use ApiMain;
use ApiResult;
use MediaWiki\Api\Hook\ApiOpenSearchSuggestHook;
use MediaWiki\MediaWikiServices;
use MediaWiki\Request\FauxRequest;

/**
 * @license GPL-2.0-or-later
 */
class Hooks implements ApiOpenSearchSuggestHook {

	/**
	 * ApiOpenSearchSuggest hook handler
	 * @param array &$results Array of search results
	 */
	public function onApiOpenSearchSuggest( &$results ) {
		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'textextracts' );
		if ( !$config->get( 'ExtractsExtendOpenSearchXml' ) || $results === [] ) {
			return;
		}

		foreach ( array_chunk( array_keys( $results ), ApiBase::LIMIT_SML1 ) as $pageIds ) {
			$api = new ApiMain( new FauxRequest(
				[
					'action' => 'query',
					'prop' => 'extracts',
					'explaintext' => true,
					'exintro' => true,
					'exlimit' => count( $pageIds ),
					'pageids' => implode( '|', $pageIds ),
				] )
			);
			$api->execute();
			$data = $api->getResult()->getResultData( [ 'query', 'pages' ] );
			foreach ( $pageIds as $id ) {
				$contentKey = $data[$id]['extract'][ApiResult::META_CONTENT] ?? '*';
				if ( isset( $data[$id]['extract'][$contentKey] ) ) {
					$results[$id]['extract'] = $data[$id]['extract'][$contentKey];
					$results[$id]['extract trimmed'] = false;
				}
			}
		}
	}
}

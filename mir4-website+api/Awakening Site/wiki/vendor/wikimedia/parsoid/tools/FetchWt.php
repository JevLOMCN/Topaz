<?php

namespace Wikimedia\Parsoid\Tools;

require_once __DIR__ . '/FetchingTool.php';

use Wikimedia\Parsoid\Config\Api\ApiHelper;

/**
 * Class FetchWt
 * Fetch the wikitext for a page, given title or revision id.
 *
 *  This is very useful for extracting test cases which can then be passed
 *  to bin/parse.php.
 */
class FetchWt extends FetchingTool {

	/** Creates supported parameters and description for the fetchwt script and adds the
	 * generic ones
	 */
	public function addDefaultParams(): void {
		$this->addOption( 'output', 'Write page to given file', false, true );

		$this->addOption( 'prefix',
			'Which wiki prefix to use; e.g. "enwiki" for English wikipedia, "eswiki" ' .
			'for Spanish, "mediawikiwiki" for mediawiki.org', false, true );

		$this->addOption( 'domain', 'Which wiki to use; e.g. "en.wikipedia.org" ' .
			'for English wikipedia, "es.wikipedia.org" for Spanish, ' .
			'"www.mediawiki.org" for mediawiki.org', false, true );

		$this->addOption( 'revid', 'Page revision to fetch', false, true );

		$this->addOption( 'title', 'Page title to fetch (only if revid is not present)',
			false, true );

		parent::addDefaultParams();
	}

	public function execute() {
		$this->maybeHelp();

		if ( $this->hasOption( 'title' ) && $this->hasOption( 'revid' ) ) {
			die( 'Can\'t specify title and revid at the same time.' );
		}
		if ( !$this->hasOption( 'title' ) && !$this->hasOption( 'revid' ) ) {
			die( 'Must specify a title or revision id.' );
		}

		$configOpts = [];
		if ( $this->hasOption( 'title' ) ) {
			$configOpts['title'] = $this->getOption( 'title' );
		}

		if ( $this->hasOption( 'revid' ) ) {
			$configOpts['revId'] = (int)$this->getOption( 'revid' );
		}

		$dompref = $this->getDomainAndPrefix();
		$apiCall = $this->getApiCall( $dompref );
		$apiHelper = new ApiHelper( $apiCall );

		$apiArgs = $this->createApiArgs( $configOpts );
		$response = $apiHelper->makeRequest( $apiArgs );

		if ( isset( $response['query']['pages'] ) ) {
			$page = $response['query']['pages'][0];
			if ( isset( $page['revisions'] ) ) {
				$content = $page['revisions'][0]['content'];
				if ( $this->hasOption( 'output' ) ) {
					file_put_contents( $this->getOption( 'output' ), $content );
				} else {
					$this->output( $content );
				}
			} else {
				die( 'Could not retrieve page content' );
			}
		} else {
			die( 'Could not retrieve page content' );
		}
	}

	/**
	 * Create the array of options needed for the API call
	 * @param array $configOpts
	 * @return array
	 */
	private function createApiArgs( array $configOpts ): array {
		$apiArgs = [
			'format' => 'json',
			'action' => 'query',
			'prop' => 'revisions',
			'rawcontinue' => 1,
			'rvprop' => 'content'
		];

		if ( isset( $configOpts['revId'] ) ) {
			$apiArgs['revids'] = $configOpts['revId'];
		} else {
			$apiArgs['titles'] = $configOpts['title'];
		}
		return $apiArgs;
	}
}

$maintClass = FetchWt::class;
require_once PARSOID_RUN_MAINTENANCE_IF_MAIN;

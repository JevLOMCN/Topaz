<?php

namespace MediaWiki\Extension\Notifications;

use ApiMain;
use CentralAuthSessionProvider;
use Exception;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use MediaWiki\Request\FauxRequest;
use MediaWiki\Request\WebRequest;
use MediaWiki\Session\SessionManager;
use MediaWiki\User\CentralId\CentralIdLookup;
use MediaWiki\User\User;
use MediaWiki\User\UserIdentity;
use MediaWiki\WikiMap\WikiMap;
use MWExceptionHandler;
use RequestContext;

class ForeignWikiRequest {

	/** @var User */
	protected $user;

	/** @var array */
	protected $params;

	/** @var array */
	protected $wikis;

	/** @var string|null */
	protected $wikiParam;

	/** @var string */
	protected $method;

	/** @var string|null */
	protected $tokenType;

	/** @var string[]|null */
	protected $csrfTokens;

	/**
	 * @param User $user
	 * @param array $params Request parameters
	 * @param array $wikis Wikis to send the request to
	 * @param string|null $wikiParam Parameter name to set to the name of the wiki
	 * @param string|null $postToken If set, use POST requests and inject a token of this type;
	 *  if null, use GET requests.
	 */
	public function __construct( User $user, array $params, array $wikis, $wikiParam = null, $postToken = null ) {
		$this->user = $user;
		$this->params = $params;
		$this->wikis = $wikis;
		$this->wikiParam = $wikiParam;
		$this->method = $postToken === null ? 'GET' : 'POST';
		$this->tokenType = $postToken;

		$this->csrfTokens = null;
	}

	/**
	 * Execute the request
	 * @param WebRequest|null $originalRequest Original request data to be sent with these requests
	 * @return array[] [ wiki => result ]
	 */
	public function execute( ?WebRequest $originalRequest = null ) {
		if ( !$this->canUseCentralAuth() ) {
			return [];
		}

		$reqs = $this->getRequestParams(
			$this->method,
			function ( string $wiki ) use ( $originalRequest ) {
				return $this->getQueryParams( $wiki, $originalRequest );
			},
			$originalRequest
		);
		return $this->doRequests( $reqs );
	}

	/**
	 * @param UserIdentity $user
	 * @return int
	 */
	protected function getCentralId( $user ) {
		return MediaWikiServices::getInstance()
			->getCentralIdLookup()
			->centralIdFromLocalUser( $user, CentralIdLookup::AUDIENCE_RAW );
	}

	protected function canUseCentralAuth() {
		global $wgFullyInitialised;

		return $wgFullyInitialised &&
			RequestContext::getMain()->getUser()->isSafeToLoad() &&
			$this->user->isSafeToLoad() &&
			SessionManager::getGlobalSession()->getProvider() instanceof CentralAuthSessionProvider &&
			$this->getCentralId( $this->user ) !== 0;
	}

	/**
	 * Returns CentralAuth token, or null on failure.
	 *
	 * @param User $user
	 * @return string|null
	 */
	protected function getCentralAuthToken( User $user ) {
		$context = new RequestContext;
		$context->setRequest( new FauxRequest( [ 'action' => 'centralauthtoken' ] ) );
		$context->setUser( $user );

		$api = new ApiMain( $context );

		try {
			$api->execute();

			return $api->getResult()->getResultData( [ 'centralauthtoken', 'centralauthtoken' ] );
		} catch ( Exception $ex ) {
			LoggerFactory::getInstance( 'Echo' )->debug(
				'Exception when fetching CentralAuth token: wiki: {wiki}, userName: {userName}, ' .
					'userId: {userId}, centralId: {centralId}, exception: {exception}',
				[
					'wiki' => WikiMap::getCurrentWikiId(),
					'userName' => $user->getName(),
					'userId' => $user->getId(),
					'centralId' => $this->getCentralId( $user ),
					'exception' => $ex,
				]
			);

			MWExceptionHandler::logException( $ex );

			return null;
		}
	}

	/**
	 * Get the CSRF token for a given wiki.
	 * This method fetches the tokens for all requested wikis at once and caches the result.
	 *
	 * @param string $wiki Name of the wiki to get a token for
	 * @param WebRequest|null $originalRequest Original request data to be sent with these requests
	 * @return string Token, or empty string if an unable to retrieve the token.
	 */
	protected function getCsrfToken( $wiki, ?WebRequest $originalRequest ) {
		if ( $this->csrfTokens === null ) {
			$this->csrfTokens = [];
			$reqs = $this->getRequestParams( 'GET', function ( string $wiki ) {
				// This doesn't depend on the wiki, but 'centralauthtoken' must be different every time
				return [
					'action' => 'query',
					'meta' => 'tokens',
					'type' => $this->tokenType,
					'format' => 'json',
					'formatversion' => '1',
					'errorformat' => 'bc',
					'centralauthtoken' => $this->getCentralAuthToken( $this->user ),
				];
			}, $originalRequest );
			$responses = $this->doRequests( $reqs );
			foreach ( $responses as $w => $response ) {
				if ( isset( $response['query']['tokens']['csrftoken'] ) ) {
					$this->csrfTokens[$w] = $response['query']['tokens']['csrftoken'];
				} else {
					LoggerFactory::getInstance( 'Echo' )->warning(
						__METHOD__ . ': Unexpected CSRF token API response from {wiki}',
						[
							'wiki' => $wiki,
							'response' => $response,
						]
					);
				}
			}
		}
		return $this->csrfTokens[$wiki] ?? '';
	}

	/**
	 * @param string $method 'GET' or 'POST'
	 * @param callable $makeParams Callback that takes a wiki name and returns an associative array of
	 *  query string / POST parameters
	 * @param WebRequest|null $originalRequest Original request data to be sent with these requests
	 * @return array[] Array of request parameters to pass to doRequests(), keyed by wiki name
	 */
	protected function getRequestParams( $method, $makeParams, ?WebRequest $originalRequest ) {
		$apis = ForeignNotifications::getApiEndpoints( $this->wikis );
		if ( !$apis ) {
			return [];
		}

		$reqs = [];
		foreach ( $apis as $wiki => $api ) {
			$queryKey = $method === 'POST' ? 'body' : 'query';
			$reqs[$wiki] = [
				'method' => $method,
				'url' => $api['url'],
				$queryKey => $makeParams( $wiki )
			];

			if ( $originalRequest ) {
				$reqs[$wiki]['headers'] = [
					'X-Forwarded-For' => $originalRequest->getIP(),
					'User-Agent' => (
						$originalRequest->getHeader( 'User-Agent' )
						. ' (via ForeignWikiRequest MediaWiki/' . MW_VERSION . ')'
					),
				];
			}
		}

		return $reqs;
	}

	/**
	 * @param string $wiki Wiki name
	 * @param WebRequest|null $originalRequest Original request data to be sent with these requests
	 * @return array
	 */
	protected function getQueryParams( $wiki, ?WebRequest $originalRequest ) {
		$extraParams = [];
		if ( $this->wikiParam ) {
			// Only request data from that specific wiki, or they'd all spawn
			// cross-wiki api requests...
			$extraParams[$this->wikiParam] = $wiki;
		}
		if ( $this->method === 'POST' ) {
			$extraParams['token'] = $this->getCsrfToken( $wiki, $originalRequest );
		}

		return [
			'centralauthtoken' => $this->getCentralAuthToken( $this->user ),
			// once all the results are gathered & merged, they'll be output in the
			// user requested format
			// but this is going to be an internal request & we don't want those
			// results in the format the user requested but in a fixed format that
			// we can interpret here
			'format' => 'json',
			'formatversion' => '1',
			'errorformat' => 'bc',
		] + $extraParams + $this->params;
	}

	/**
	 * @param array $reqs API request params
	 * @return array[]
	 * @throws Exception
	 */
	protected function doRequests( array $reqs ) {
		$http = MediaWikiServices::getInstance()->getHttpRequestFactory()->createMultiClient();
		$responses = $http->runMulti( $reqs );

		$results = [];
		foreach ( $responses as $wiki => $response ) {
			$statusCode = $response['response']['code'];

			if ( $statusCode >= 200 && $statusCode <= 299 ) {
				$parsed = json_decode( $response['response']['body'], true );
				if ( $parsed ) {
					$results[$wiki] = $parsed;
				}
			}

			if ( !isset( $results[$wiki] ) ) {
				LoggerFactory::getInstance( 'Echo' )->warning(
					'Failed to fetch API response from {wiki}. Error: {error}',
					[
						'wiki' => $wiki,
						'error' => $response['response']['error'] ?? 'unknown',
						'statusCode' => $statusCode,
						'response' => $response['response'],
						'request' => $reqs[$wiki],
					]
				);
			}
		}

		return $results;
	}
}

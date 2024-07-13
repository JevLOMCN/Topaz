<?php
/**
 * MediaWiki math extension
 *
 * @copyright 2002-2015 various MediaWiki contributors
 * @license GPL-2.0-or-later
 */

namespace MediaWiki\Extension\Math;

use Exception;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use Psr\Log\LoggerInterface;
use stdClass;

class MathRestbaseInterface {
	/** @var string|false */
	private $hash = false;
	/** @var string */
	private $tex;
	/** @var string */
	private $type;
	private ?string $checkedTex = null;
	/** @var bool|null */
	private $success;
	/** @var array */
	private $identifiers;
	/** @var stdClass|null */
	private $error;
	/** @var string|null */
	private $mathoidStyle;
	/** @var string|null */
	private $mml;
	/** @var array */
	private $warnings = [];
	/** @var bool is there a request to purge the existing mathematical content */
	private $purge = false;
	/** @var LoggerInterface */
	private $logger;

	/**
	 * @param string $tex
	 * @param string $type
	 */
	public function __construct( $tex = '', $type = 'tex' ) {
		$this->tex = $tex;
		$this->type = $type;
		$this->logger = LoggerFactory::getInstance( 'Math' );
	}

	/**
	 * Bundles several requests for fetching MathML.
	 * Does not send requests, if the input TeX is invalid.
	 * @param MathRestbaseInterface[] $rbis
	 * @param \MultiHttpClient $multiHttpClient
	 */
	private static function batchGetMathML( array $rbis, \MultiHttpClient $multiHttpClient ) {
		$requests = [];
		$skips = [];
		$i = 0;
		foreach ( $rbis as $rbi ) {
			/** @var MathRestbaseInterface $rbi */
			if ( $rbi->getSuccess() ) {
				$requests[] = $rbi->getContentRequest( 'mml' );
			} else {
				$skips[] = $i;
			}
			$i++;
		}
		$results = $multiHttpClient->runMulti( $requests );
		$lenRbis = count( $rbis );
		$j = 0;
		for ( $i = 0; $i < $lenRbis; $i++ ) {
			if ( !in_array( $i, $skips, true ) ) {
				/** @var MathRestbaseInterface $rbi */
				$rbi = $rbis[$i];
				try {
					$response = $results[ $j ][ 'response' ];
					$mml = $rbi->evaluateContentResponse( 'mml', $response, $requests[$j] );
					$rbi->mml = $mml;
				} catch ( MathRestbaseException $e ) {
					// FIXME: Why is this silenced? Doesn't this leave invalid data behind?
				}
				$j++;
			}
		}
	}

	/**
	 * Lets this instance know if this is a purge request. When set to true,
	 * it will cause the object to issue the first content request with a
	 * 'Cache-Control: no-cache' header to prompt the regeneration of the
	 * renders.
	 *
	 * @param bool $purge whether this is a purge request
	 */
	public function setPurge( $purge = true ) {
		$this->purge = $purge;
	}

	/**
	 * @return string MathML code
	 * @throws MathRestbaseException
	 */
	public function getMathML() {
		if ( !$this->mml ) {
			$this->mml = $this->getContent( 'mml' );
		}
		return $this->mml;
	}

	/**
	 * @param string $type
	 * @return string
	 * @throws MathRestbaseException
	 */
	private function getContent( $type ) {
		$request = $this->getContentRequest( $type );
		$multiHttpClient = $this->getMultiHttpClient();
		$response = $multiHttpClient->run( $request );
		return $this->evaluateContentResponse( $type, $response, $request );
	}

	/**
	 * @throws InvalidTeXException
	 */
	private function calculateHash() {
		if ( !$this->hash ) {
			if ( !$this->checkTeX() ) {
				throw new InvalidTeXException( "TeX input is invalid." );
			}
		}
	}

	public function checkTeX() {
		$request = $this->getCheckRequest();
		$requestResult = $this->executeRestbaseCheckRequest( $request );
		return $this->evaluateRestbaseCheckResponse( $requestResult );
	}

	/**
	 * Performs a service request
	 * Generates error messages on failure
	 * @see MediaWiki\Http\HttpRequestFactory::post()
	 *
	 * @param array $request
	 * @return array
	 */
	private function executeRestbaseCheckRequest( $request ) {
		$multiHttpClient = $this->getMultiHttpClient();
		$response = $multiHttpClient->run( $request );
		if ( $response['code'] !== 200 ) {
			$this->logger->info( 'Tex check failed', [
				'post'  => $request['body'],
				'error' => $response['error'],
				'urlparams'   => $request['url']
			] );
		}
		return $response;
	}

	/**
	 * @param MathRestbaseInterface[] $rbis
	 */
	public static function batchEvaluate( array $rbis ) {
		if ( count( $rbis ) == 0 ) {
			return;
		}
		$requests = [];
		/** @var MathRestbaseInterface $first */
		$first = $rbis[0];
		$multiHttpClient = $first->getMultiHttpClient();
		foreach ( $rbis as $rbi ) {
			/** @var MathRestbaseInterface $rbi */
			$requests[] = $rbi->getCheckRequest();
		}
		$results = $multiHttpClient->runMulti( $requests );
		$i = 0;
		foreach ( $results as $requestResponse ) {
			/** @var MathRestbaseInterface $rbi */
			$rbi = $rbis[$i++];
			try {
				$response = $requestResponse[ 'response' ];
				$rbi->evaluateRestbaseCheckResponse( $response );
			} catch ( Exception $e ) {
			}
		}
		self::batchGetMathML( $rbis, $multiHttpClient );
	}

	private function getMultiHttpClient() {
		global $wgMathConcurrentReqs;
		$multiHttpClient = MediaWikiServices::getInstance()->getHttpRequestFactory()->createMultiClient(
			[ 'maxConnsPerHost' => $wgMathConcurrentReqs ] );

		return $multiHttpClient;
	}

	/**
	 * The URL is generated according to the following logic:
	 *
	 * Case A: <code>$internal = false</code>, which means one needs a URL that is accessible from
	 * outside:
	 *
	 * --> Use <code>$wgMathFullRestbaseURL</code>. It must always be configured.
	 *
	 * Case B: <code>$internal = true</code>, which means one needs to access content from Restbase
	 * which does not need to be accessible from outside:
	 *
	 * --> Use the mount point when it is available and <code>$wgMathUseInternalRestbasePath =
	 * true</code>. If not, use <code>$wgMathFullRestbaseURL</code>.
	 *
	 * @param string $path
	 * @param bool|true $internal
	 * @return string
	 */
	public function getUrl( $path, $internal = true ) {
		global $wgMathInternalRestbaseURL, $wgMathFullRestbaseURL;
		if ( $internal ) {
			return "{$wgMathInternalRestbaseURL}v1/$path";
		} else {
			return "{$wgMathFullRestbaseURL}v1/$path";
		}
	}

	/**
	 * @return string
	 * @throws MathRestbaseException
	 */
	public function getSvg() {
		return $this->getContent( 'svg' );
	}

	/**
	 * Generates a unique TeX string, renders it and gets it via a public URL.
	 * The method fails, if the public URL does not point to the same server, who did render
	 * the unique TeX input in the first place.
	 * @return bool
	 */
	private function checkConfig() {
		// Generates a TeX string that probably has not been generated before
		$uniqueTeX = uniqid( 't=', true );
		$testInterface = new MathRestbaseInterface( $uniqueTeX );
		if ( !$testInterface->checkTeX() ) {
			$this->logger->warning( 'Config check failed, since test expression was considered as invalid.',
				[ 'uniqueTeX' => $uniqueTeX ] );
			return false;
		}

		try {
			$url = $testInterface->getFullSvgUrl();
			$req = MediaWikiServices::getInstance()->getHttpRequestFactory()->create( $url, [], __METHOD__ );
			$status = $req->execute();
			if ( $status->isOK() ) {
				return true;
			}

			$this->logger->warning( 'Config check failed, due to an invalid response code.',
				[ 'responseCode' => $status ] );
		} catch ( Exception $e ) {
			$this->logger->warning( 'Config check failed, due to an exception.', [ $e ] );
		}

		return false;
	}

	/**
	 * Gets a publicly accessible link to the generated SVG image.
	 * @return string
	 * @throws InvalidTeXException
	 */
	public function getFullSvgUrl() {
		$this->calculateHash();
		return $this->getUrl( "media/math/render/svg/{$this->hash}", false );
	}

	public function getCheckedTex(): ?string {
		return $this->checkedTex;
	}

	public function getSuccess(): bool {
		if ( $this->success === null ) {
			$this->checkTeX();
		}
		return $this->success;
	}

	public function getIdentifiers(): ?array {
		return $this->identifiers;
	}

	public function getError(): ?stdClass {
		return $this->error;
	}

	public function getTex(): string {
		return $this->tex;
	}

	public function getType(): string {
		return $this->type;
	}

	private function setErrorMessage( $msg ) {
		$this->error = (object)[ 'error' => (object)[ 'message' => $msg ] ];
	}

	public function getWarnings(): array {
		return $this->warnings;
	}

	public function getCheckRequest(): array {
		return [
			'method' => 'POST',
			'body'   => [
				'type' => $this->type,
				'q'    => $this->tex
			],
			'url'    => $this->getUrl( "media/math/check/{$this->type}" )
		];
	}

	public function evaluateRestbaseCheckResponse( array $response ): bool {
		$json = json_decode( $response['body'] );
		if ( $response['code'] === 200 &&
				isset( $json->success ) &&
				isset( $json->checked ) &&
				isset( $json->identifiers ) ) {
			$headers = $response['headers'];
			$this->hash = $headers['x-resource-location'];
			$this->success = $json->success;
			$this->checkedTex = $json->checked;
			$this->identifiers = $json->identifiers;
			if ( isset( $json->warnings ) ) {
				$this->warnings = $json->warnings;
			}
			return true;
		}
		if ( isset( $json->detail->success ) ) {
			$this->success = $json->detail->success;
			$this->error = $json->detail;
			return false;
		}
		$this->success = false;
		$this->setErrorMessage( 'Math extension cannot connect to Restbase.' );
		$this->logger->error( 'Received invalid response from restbase.', [
			'body' => $response['body'],
			'code' => $response['code'] ] );
		return false;
	}

	public function getMathoidStyle(): ?string {
		return $this->mathoidStyle;
	}

	/**
	 * @param string $type
	 * @return array
	 * @throws InvalidTeXException
	 */
	private function getContentRequest( $type ) {
		$this->calculateHash();
		$request = [
			'method' => 'GET',
			'url' => $this->getUrl( "media/math/render/$type/{$this->hash}" )
		];
		if ( $this->purge ) {
			$request['headers'] = [
				'Cache-Control' => 'no-cache'
			];
			$this->purge = false;
		}
		return $request;
	}

	/**
	 * @param string $type
	 * @param array $response
	 * @param array $request
	 * @return string
	 * @throws MathRestbaseException
	 */
	private function evaluateContentResponse( $type, array $response, array $request ) {
		if ( $response['code'] === 200 ) {
			if ( array_key_exists( 'x-mathoid-style', $response['headers'] ) ) {
				$this->mathoidStyle = $response['headers']['x-mathoid-style'];
			}
			return $response['body'];
		}
		// Remove "convenience" duplicate keys put in place by MultiHttpClient
		unset( $response[0], $response[1], $response[2], $response[3], $response[4] );
		$this->logger->error( 'Restbase math server problem', [
			'urlparams' => $request['url'],
			'response' => [ 'code' => $response['code'], 'body' => $response['body'] ],
			'math_type' => $type,
			'tex' => $this->tex
		] );
		self::throwContentError( $type, $response['body'] );
	}

	/**
	 * @param string $type
	 * @param string $body
	 * @throws MathRestbaseException
	 * @return never
	 */
	public static function throwContentError( $type, $body ) {
		$detail = 'Server problem.';
		$json = json_decode( $body );
		if ( isset( $json->detail ) ) {
			if ( is_array( $json->detail ) ) {
				$detail = $json->detail[0];
			} elseif ( is_string( $json->detail ) ) {
				$detail = $json->detail;
			}
		}
		throw new MathRestbaseException( "Cannot get $type. $detail" );
	}
}

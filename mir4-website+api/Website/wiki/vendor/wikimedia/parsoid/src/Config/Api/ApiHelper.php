<?php

declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Config\Api;

use Wikimedia\ScopedCallback;

class ApiHelper {

	/** @var string */
	private $endpoint;

	/** @var array */
	private $curlopt;

	/** @var string */
	private $cacheDir;

	/** @var bool|string */
	private $writeToCache;

	/**
	 * @param array $opts
	 *  - apiEndpoint: (string) URL for api.php. Required.
	 *  - apiTimeout: (int) Timeout, in sections. Default 60.
	 *  - userAgent: (string) User agent prefix.
	 *  - cacheDir: (string) If present, looks aside to the specified directory
	 *    for a cached response before making a network request.
	 *  - writeToCache: (bool|string) If present and truthy, writes successful
	 *    network requests to `cacheDir` so they can be reused.  If set to
	 *    the string 'pretty', prettifies the JSON returned before writing it.
	 */
	public function __construct( array $opts ) {
		if ( !isset( $opts['apiEndpoint'] ) ) {
			throw new \InvalidArgumentException( '$opts[\'apiEndpoint\'] must be set' );
		}
		$this->endpoint = $opts['apiEndpoint'];

		$this->cacheDir = $opts['cacheDir'] ?? null;
		$this->writeToCache = $opts['writeToCache'] ?? false;

		$this->curlopt = [
			CURLOPT_USERAGENT => trim( ( $opts['userAgent'] ?? '' ) . ' ApiEnv/1.0 Parsoid-PHP/0.1' ),
			CURLOPT_CONNECTTIMEOUT => $opts['apiTimeout'] ?? 60,
			CURLOPT_TIMEOUT => $opts['apiTimeout'] ?? 60,
			CURLOPT_FOLLOWLOCATION => false,
			CURLOPT_ENCODING => '', // Enable compression
			CURLOPT_SAFE_UPLOAD => true,
			CURLOPT_RETURNTRANSFER => true,
		];
	}

	/**
	 * Make an API request
	 * @param array $params API parameters
	 * @return array API response data
	 */
	public function makeRequest( array $params ): array {
		$filename = null;
		$params += [ 'formatversion' => 2 ];
		if ( $this->cacheDir !== null ) {
			# sort the parameters for a repeatable filename
			ksort( $params );
			$query = $this->endpoint . "?" . http_build_query( $params );
			$queryHash = hash( 'sha256', $query );
			$filename = $this->cacheDir . DIRECTORY_SEPARATOR .
				parse_url( $query, PHP_URL_HOST ) . '-' .
				substr( $queryHash, 0, 8 );
			if ( file_exists( $filename ) ) {
				$res = file_get_contents( $filename );
				$filename = null; // We don't need to write this back
			} else {
				$res = $this->makeCurlRequest( $params );
			}
		} else {
			$res = $this->makeCurlRequest( $params );
		}

		$data = json_decode( $res, true );
		if ( !is_array( $data ) ) {
			throw new \RuntimeException( "HTTP request failed: Response was not a JSON array" );
		}

		if ( isset( $data['error'] ) ) {
			$e = $data['error'];
			throw new \RuntimeException( "MediaWiki API error: [{$e['code']}] {$e['info']}" );
		}

		if ( $filename && $this->writeToCache ) {
			if ( $this->writeToCache === 'pretty' ) {
				/* Prettify the results */
				$dataPretty = [
					'__endpoint__' => $this->endpoint,
					'__params__' => $params,
				] + $data;
				$res = json_encode(
					$dataPretty, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
				);
			}
			file_put_contents( $filename, $res );
		}

		return $data;
	}

	private function makeCurlRequest( array $params ): string {
		$ch = curl_init( $this->endpoint );
		if ( !$ch ) {
			throw new \RuntimeException( "Failed to open curl handle to $this->endpoint" );
		}
		$reset = new ScopedCallback( 'curl_close', [ $ch ] );

		$params['format'] = 'json';
		$params['formatversion'] ??= '2';

		$opts = [
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $params,
		] + $this->curlopt;
		if ( !curl_setopt_array( $ch, $opts ) ) {
			throw new \RuntimeException( "Error setting curl options: " . curl_error( $ch ) );
		}

		$res = curl_exec( $ch );

		if ( curl_errno( $ch ) !== 0 ) {
			throw new \RuntimeException( "HTTP request failed: " . curl_error( $ch ) );
		}

		$code = curl_getinfo( $ch, CURLINFO_RESPONSE_CODE );
		if ( $code !== 200 ) {
			throw new \RuntimeException( "HTTP request failed: HTTP code $code" );
		}

		ScopedCallback::consume( $reset );

		if ( !$res ) {
			throw new \RuntimeException( "HTTP request failed: Empty response" );
		}

		return $res;
	}
}

<?php

namespace Test\Parsoid\Config\Api;

use PHPUnit\Framework\TestCase;
use Wikimedia\Parsoid\Config\Api\ApiHelper;
use Wikimedia\Parsoid\Utils\PHPUtils;
use Wikimedia\ScopedCallback;

class TestApiHelper extends ApiHelper {

	/** @var TestCase */
	private $test;

	/** @var array|null */
	private $params;

	/** @var array|null */
	private $ret;

	/**
	 * @param TestCase $test
	 * @param string $filename Data file name to use
	 */
	public function __construct( TestCase $test, string $filename ) {
		$file = __DIR__ . '/data/' . $filename . '.reqdata';
		$data = explode( "\n", trim( file_get_contents( $file ) ), 3 );
		if ( count( $data ) !== 2 ) {
			throw new \InvalidArgumentException( "Bad request data file: $file" );
		}

		$this->test = $test;
		$this->params = json_decode( $data[0], true );
		$this->ret = json_decode( $data[1], true );
	}

	/** @inheritDoc */
	public function makeRequest( array $params ): array {
		if ( $this->ret === null ) {
			$this->test->fail( __METHOD__ . ' should only be called once' );
		}
		$this->test->assertEquals( $this->params, $params );

		$ret = $this->ret;
		$this->ret = null;
		return $ret;
	}

	/**
	 * Create the reqdata file
	 * @param string $filename Data file to write
	 * @param array $params API request parameters
	 */
	public static function writeRequestFile( string $filename, array $params ): void {
		$out = PHPUtils::jsonEncode( $params ) . "\n";

		$ch = curl_init( 'https://en.wikipedia.org/w/api.php' );
		if ( !$ch ) {
			throw new \RuntimeException( "Failed to open curl handle" );
		}
		$reset = new ScopedCallback( 'curl_close', [ $ch ] );

		$params['format'] = 'json';
		$params['formatversion'] ??= '2';

		$curlopt = [
			CURLOPT_USERAGENT => 'ApiEnv/1.0 Parsoid-PHP/0.1',
			CURLOPT_CONNECTTIMEOUT => 60,
			CURLOPT_TIMEOUT => 60,
			CURLOPT_FOLLOWLOCATION => false,
			CURLOPT_ENCODING => '', // Enable compression
			CURLOPT_SAFE_UPLOAD => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $params,
		];
		if ( !curl_setopt_array( $ch, $curlopt ) ) {
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

		$data = json_decode( $res, true );
		if ( !is_array( $data ) ) {
			throw new \RuntimeException( "HTTP request failed: Response was not a JSON array" );
		}

		if ( isset( $data['error'] ) ) {
			$e = $data['error'];
			throw new \RuntimeException( "MediaWiki API error: [{$e['code']}] {$e['info']}" );
		}

		$out .= rtrim( $res ) . "\n";
		$file = __DIR__ . '/data/' . $filename . '.reqdata';
		file_put_contents( $file, $out );
	}

}

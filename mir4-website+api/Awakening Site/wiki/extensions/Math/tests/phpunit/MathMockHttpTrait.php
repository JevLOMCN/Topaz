<?php

namespace MediaWiki\Extension\Math\Tests;

use MockHttpTrait;
use MultiHttpClient;
use PHPUnit\Framework\MockObject\MockObject;

trait MathMockHttpTrait {
	use MockHttpTrait;

	protected function setupBadMathRestBaseMockHttp() {
		$this->installMockHttp(
			$this->makeFakeHttpMultiClient( [ [
				'code' => 400,
				'body' => file_get_contents( __DIR__ . '/InputCheck/data/restbase/fail.json' ),
			] ] )
		);
	}

	protected function setupSyntaxErrorRestBaseMockHttp() {
		$this->installMockHttp(
			$this->makeFakeHttpMultiClient( [ [
				'code' => 400,
				'body' => file_get_contents( __DIR__ . '/InputCheck/data/restbase/syntax_error.json' ),
			] ] )
		);
	}

	protected function setupGoodMathRestBaseMockHttp( bool $withSvg = false ) {
		$requests = [
			$this->makeFakeHttpMultiClient( [ [
				'code' => 200,
				'headers' => [
					'x-resource-location' => 'RESOURCE_LOCATION',
				],
				'body' => file_get_contents( __DIR__ . '/InputCheck/data/restbase/sinx.json' ),
			] ] ),
			$this->makeFakeHttpMultiClient( [ file_get_contents( __DIR__ . '/data/restbase/sinx.mml' ), ] ),
		];
		if ( $withSvg ) {
			$requests[] = $this->makeFakeHttpMultiClient( [ 'SVGSVSVG', ] );
		}
		$this->installMockHttp( $requests );
	}

	protected function setupGoodChemRestBaseMockHttp() {
		$this->installMockHttp( [
			$this->makeFakeHttpMultiClient( [ [
				'code' => 200,
				'headers' => [
					'x-resource-location' => 'RESOURCE_LOCATION',
				],
				'body' => file_get_contents( __DIR__ . '/InputCheck/data/restbase/chem.json' ),
			] ] ),
			$this->makeFakeHttpMultiClient( [ file_get_contents( __DIR__ . '/data/restbase/h2o.mml' ), ] ),
		] );
	}

	/**
	 * Install a mock HTTP handler that expects a specific set of requests to be made.
	 *
	 * @param array $expectedList A list of expected requests
	 */
	protected function expectMathRestBaseMockHttpRequest( array $expectedList, array $responseList ) {
		/** @var MockObject|MultiHttpClient $client */
		$mockHttpRequestMulti = $this->createNoOpMock(
			MultiHttpClient::class,
			[ 'run', 'runMulti' ]
		);

		$mockHttpRequestMulti->method( 'run' )->willReturnCallback(
			static function ( array $req, array $opts = [] ) use ( $mockHttpRequestMulti ) {
				return $mockHttpRequestMulti->runMulti( [ $req ], $opts )[0]['response'];
			}
		);

		$mockHttpRequestMulti->method( 'runMulti' )
			->willReturnCallback( function ( array $reqs, array $opts = [] ) use ( &$expectedList, $responseList ) {
				// for each result
				foreach ( $reqs as $i => $req ) {
					$expected = array_shift( $expectedList );
					$response = array_shift( $responseList );

					if ( $expected ) {
						// assert fields
						foreach ( $expected as $k => $v ) {
							$this->assertSame( $v, $req[$k], "Request field $k" );
						}
					}

					$reqs[$i]['response'] = [ 'code' => 200, ] + $response;
				}

				return $reqs;
			} );

		$this->installMockHttp( $mockHttpRequestMulti );
	}
}

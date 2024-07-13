<?php

namespace MediaWiki\Extension\VisualEditor\Tests;

use MediaWiki\Extension\VisualEditor\DirectParsoidClient;
use MediaWiki\Extension\VisualEditor\VisualEditorParsoidClientFactory;
use MediaWiki\Http\HttpRequestFactory;
use MediaWiki\Permissions\Authority;
use MediaWiki\Rest\Handler\Helper\PageRestHelperFactory;
use MediaWikiIntegrationTestCase;
use MultiHttpClient;

/**
 * @coversDefaultClass \MediaWiki\Extension\VisualEditor\VisualEditorParsoidClientFactory
 */
class VisualEditorParsoidClientFactoryTest extends MediaWikiIntegrationTestCase {

	/**
	 * @covers ::__construct
	 */
	public function testGetVisualEditorParsoidClientFactory() {
		$veParsoidClientFactory = $this->getServiceContainer()
			->get( VisualEditorParsoidClientFactory::SERVICE_NAME );

		$this->assertInstanceOf( VisualEditorParsoidClientFactory::class, $veParsoidClientFactory );
	}

	private function newClientFactory() {
		$httpRequestFactory = $this->createNoOpMock( HttpRequestFactory::class, [ 'createMultiClient' ] );
		$httpRequestFactory->method( 'createMultiClient' )->willReturn(
			$this->createNoOpMock( MultiHttpClient::class )
		);

		return new VisualEditorParsoidClientFactory(
			$this->createNoOpMock( PageRestHelperFactory::class )
		);
	}

	/**
	 * @covers ::createParsoidClient
	 */
	public function testGetClient() {
		$authority = $this->createNoOpMock( Authority::class );

		$factory = $this->newClientFactory();

		$client = $factory->createParsoidClient( $authority );
		$this->assertInstanceOf( DirectParsoidClient::class, $client );
	}
}

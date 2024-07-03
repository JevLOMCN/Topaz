<?php

namespace Cite\Tests;

use Cite\ResourceLoader\CiteCSSFileModule;
use MediaWiki\ResourceLoader\Context;

/**
 * @covers \Cite\ResourceLoader\CiteCSSFileModule
 * @license GPL-2.0-or-later
 */
class CiteCSSFileModuleTest extends \MediaWikiIntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();

		$this->setService(
			'ContentLanguage',
			$this->getServiceContainer()->getLanguageFactory()->getLanguage( 'fa' )
		);
	}

	public function testModule() {
		$module = new CiteCSSFileModule( [], __DIR__ . '/../../modules/parsoid-styles' );
		$styles = $module->getStyleFiles( $this->createMock( Context::class ) );
		$this->assertSame( [ 'ext.cite.style.fa.css' ], $styles['all'] );
	}

}

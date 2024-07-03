<?php

namespace MediaWiki\Minerva;

use MediaWiki\Minerva\Skins\FeaturesHelper;
use MediaWiki\Request\WebRequest;
use MediaWiki\Title\Title;
use MediaWikiIntegrationTestCase;

/**
 * @group MinervaNeue
 * @coversDefaultClass \MediaWiki\Minerva\Skins\FeaturesHelper
 */
class FeaturesHelperTest extends MediaWikiIntegrationTestCase {

	public function provideShouldDisableNightModeExcluded() {
		$options = [
			'exclude' => [
				'mainpage' => true,
				'pagetitles' => [ 'Test_Page' ],
				'namespaces' => [ 1 ],
				'querystring' => [
					'action' => '(edit)',
					'diff' => '.+'
				]
			]
		];
		$context = new \RequestContext();
		$request = $context->getRequest();
		$editContext = new \RequestContext();
		$editRequest = $context->getRequest();
		$editRequest->setVal( 'action', 'edit' );
		$mainTitle = Title::makeTitle( NS_MAIN, 'Main Page' );
		$testTitle = Title::makeTitle( NS_MAIN, 'Test_Page' );
		$otherTitle = Title::makeTitle( NS_MAIN, 'Other_Page' );
		$differentTitle = Title::makeTitle( NS_MAIN, 'Different Page' );
		$includedTitle = Title::makeTitle( NS_MAIN, 'Included Page' );
		yield 'main page' => [ $options, $request, $mainTitle, true ];
		yield 'page titles' => [ $options, $request, $testTitle, true ];
		yield 'namespaces' => [ $options, $request, $otherTitle, true ];
		yield 'query string' => [ $options, $editRequest, $differentTitle, true ];
	}

	/**
	 * @dataProvider provideShouldDisableNightModeExcluded
	 * @covers \MediaWiki\Minerva\Skins\FeaturesHelper::shouldDisableNightMode
	 */
	public function testShouldDisableNightModeExcluded(
		array $options, WebRequest $request, Title $title = null, bool $expected = false ) {
		$featuresHelper = new FeaturesHelper();
		$shouldDisableNightMode = $featuresHelper->shouldDisableNightMode( $options, $request, $title );
		$this->assertSame( $expected, $shouldDisableNightMode );
	}

	/**
	 * @covers \MediaWiki\Minerva\Skins\FeaturesHelper::shouldDisableNightMode
	 */
	public function testShouldDisableNightModeIncluded() {
		$options = [
			'exclude' => [
				'mainpage' => false,
				'pagetitles' => [ 'Test_Page' ],
				'namespaces' => [ 1 ],
				'querystring' => [
					'action' => '(edit)',
					'diff' => '.+'
				]
			]
		];
		$context = new \RequestContext();
		$request = $context->getRequest();
		$includedTitle = Title::makeTitle( NS_MAIN, 'Included Page' );
		$featuresHelper = new FeaturesHelper();
		$shouldDisableNightMode = $featuresHelper->shouldDisableNightMode( $options, $request, $includedTitle );
		$this->assertFalse( $shouldDisableNightMode );
	}

}

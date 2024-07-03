<?php

namespace MediaWiki\Skins\Vector\Tests\Integration;

use MediaWiki\Skins\Vector\ConfigHelper;
use RequestContext;
use Title;

/**
 * @coversDefaultClass \MediaWiki\Skins\Vector\ConfigHelper
 */
class ConfigHelperTest extends \MediaWikiIntegrationTestCase {
	/**
	 * @covers ::shouldDisable when config is empty
	 */
	public function testShouldDisableEmpty() {
		$request = RequestContext::getMain()->getRequest();

		$this->assertFalse( ConfigHelper::shouldDisable( [], $request ) );
	}

	public static function provideShouldDisableMainPage() {
		return [
			[ true ], [ false ]
		];
	}

	/**
	 * @dataProvider provideShouldDisableMainPage
	 * @covers ::shouldDisable for the main page
	 */
	public function testShouldDisableMainPage( $disable ) {
		$config = [ 'exclude' => [ 'mainpage' => $disable ] ];
		$request = RequestContext::getMain()->getRequest();
		$title = Title::makeTitle( NS_MAIN, 'Main Page' );

		$this->assertSame( ConfigHelper::shouldDisable( $config, $request, $title ), $disable );
	}

	/**
	 * @covers ::shouldDisable for the main page when mainpage is not present in the config
	 */
	public function testShouldDisableMainPageImplicit() {
		$config = [ 'exclude' => [ 'pagetitles' => [ 'Main Page' ] ] ];
		$request = RequestContext::getMain()->getRequest();
		$title = Title::makeTitle( NS_MAIN, 'Main Page' );

		$this->assertFalse( ConfigHelper::shouldDisable( $config, $request, $title ) );
	}

	/**
	 * @covers ::shouldDisable inclusion
	 */
	public function testShouldDisableInclude() {
		$config = [ 'exclude' => [ 'pagetitles' => [ 'test' ] ], 'include' => [ 'test' ] ];
		$request = RequestContext::getMain()->getRequest();
		$title = Title::makeTitle( NS_MAIN, 'test' );

		$this->assertFalse( ConfigHelper::shouldDisable( $config, $request, $title ) );
	}

	/**
	 * @covers ::shouldDisable page title exclusion
	 */
	public function testShouldDisablePageTitles() {
		$config = [ 'exclude' => [ 'pagetitles' => [ 'test' ] ] ];
		$request = RequestContext::getMain()->getRequest();
		$title = Title::makeTitle( NS_MAIN, 'test' );

		$this->assertTrue( ConfigHelper::shouldDisable( $config, $request, $title ) );
	}

	/**
	 * @covers ::shouldDisable namespace exclusion
	 */
	public function testShouldDisableNamespaces() {
		$config = [ 'exclude' => [ 'namespaces' => [ NS_SPECIAL ] ] ];
		$request = RequestContext::getMain()->getRequest();
		$title = Title::makeTitle( NS_SPECIAL, 'test' );

		$this->assertTrue( ConfigHelper::shouldDisable( $config, $request, $title ) );
	}

	/**
	 * @covers ::shouldDisable query string exclusion
	 */
	public function testShouldDisableQueryString() {
		$config = [ 'exclude' => [ 'querystring' => [ 'action' => 'test' ] ] ];
		$request = RequestContext::getMain()->getRequest();
		$title = Title::makeTitle( NS_MAIN, 'test' );

		$request->setVal( 'action', 'aaatestaaa' );

		$this->assertTrue( ConfigHelper::shouldDisable( $config, $request, $title ) );
	}

	/**
	 * @covers ::shouldDisable query string exclusion using regex
	 */
	public function testShouldDisableQueryStringRegex() {
		$config = [ 'exclude' => [ 'querystring' => [ 'action' => 'a+b.c' ] ] ];
		$request = RequestContext::getMain()->getRequest();
		$title = Title::makeTitle( NS_MAIN, 'test' );

		$request->setVal( 'action', 'aaaabbc' );

		$this->assertTrue( ConfigHelper::shouldDisable( $config, $request, $title ) );
	}

	/**
	 * @covers ::shouldDisable query string exclusion using wildcard
	 */
	public function testShouldDisableQueryStringWildcard() {
		$config = [ 'exclude' => [ 'querystring' => [ 'action' => '*' ] ] ];
		$request = RequestContext::getMain()->getRequest();
		$title = Title::makeTitle( NS_MAIN, 'test' );

		$request->setVal( 'action', 'test' );

		$this->assertTrue( ConfigHelper::shouldDisable( $config, $request, $title ) );
	}
}

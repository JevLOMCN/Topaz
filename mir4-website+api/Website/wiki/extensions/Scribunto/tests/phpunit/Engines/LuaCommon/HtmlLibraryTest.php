<?php

namespace MediaWiki\Extension\Scribunto\Tests\Engines\LuaCommon;

use Parser;

/**
 * @covers \MediaWiki\Extension\Scribunto\Engines\LuaCommon\HtmlLibrary
 */
class HtmlLibraryTest extends LuaEngineUnitTestBase {
	/** @inheritDoc */
	protected static $moduleName = 'HtmlLibraryTests';

	protected function setUp(): void {
		parent::setUp();

		// For strip marker test
		$markers = [
			'nowiki' => Parser::MARKER_PREFIX . '-test-nowiki-' . Parser::MARKER_SUFFIX,
		];
		$interpreter = $this->getEngine()->getInterpreter();
		$interpreter->callFunction(
			$interpreter->loadString( 'mw.html.stripMarkers = ...', 'fortest' ),
			$markers
		);
	}

	protected function getTestModules() {
		return parent::getTestModules() + [
			'HtmlLibraryTests' => __DIR__ . '/HtmlLibraryTests.lua',
		];
	}
}

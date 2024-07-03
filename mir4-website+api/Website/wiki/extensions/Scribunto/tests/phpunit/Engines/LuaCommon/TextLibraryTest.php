<?php

namespace MediaWiki\Extension\Scribunto\Tests\Engines\LuaCommon;

use Parser;

/**
 * @covers \MediaWiki\Extension\Scribunto\Engines\LuaCommon\TextLibrary
 */
class TextLibraryTest extends LuaEngineUnitTestBase {
	/** @inheritDoc */
	protected static $moduleName = 'TextLibraryTests';

	protected function setUp(): void {
		parent::setUp();

		// For unstrip test
		$parser = $this->getEngine()->getParser();
		$markers = [
			'nowiki' => Parser::MARKER_PREFIX . '-test-nowiki-' . Parser::MARKER_SUFFIX,
			'general' => Parser::MARKER_PREFIX . '-test-general-' . Parser::MARKER_SUFFIX,
			'ppnowiki' => Parser::MARKER_PREFIX . '-pptest-nowiki-' . Parser::MARKER_SUFFIX,
		];
		$parser->getStripState()->addNoWiki( $markers['nowiki'], 'NoWiki' );
		$parser->getStripState()->addGeneral( $markers['general'], 'General' );
		$parser->getStripState()->addNoWiki( $markers['ppnowiki'], '<nowiki>PP-NoWiki</nowiki>' );
		$interpreter = $this->getEngine()->getInterpreter();
		$interpreter->callFunction(
			$interpreter->loadString( 'mw.text.stripTest = ...', 'fortest' ),
			$markers
		);
	}

	protected function getTestModules() {
		return parent::getTestModules() + [
			'TextLibraryTests' => __DIR__ . '/TextLibraryTests.lua',
		];
	}
}

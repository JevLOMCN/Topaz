<?php

namespace MediaWiki\Extension\Scribunto\Tests\Engines\LuaCommon;

/**
 * @covers \MediaWiki\Extension\Scribunto\Engines\LuaCommon\MessageLibrary
 */
class MessageLibraryTest extends LuaEngineUnitTestBase {
	/** @inheritDoc */
	protected static $moduleName = 'MessageLibraryTests';

	protected function getTestModules() {
		return parent::getTestModules() + [
			'MessageLibraryTests' => __DIR__ . '/MessageLibraryTests.lua',
		];
	}
}

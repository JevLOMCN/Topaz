<?php

namespace MediaWiki\Extension\Scribunto\Tests\Engines\LuaCommon;

/**
 * @covers \MediaWiki\Extension\Scribunto\Engines\LuaCommon\HashLibrary
 */
class HashLibraryTest extends LuaEngineUnitTestBase {
	/** @inheritDoc */
	protected static $moduleName = 'HashLibraryTests';

	protected function getTestModules() {
		return parent::getTestModules() + [
			'HashLibraryTests' => __DIR__ . '/HashLibraryTests.lua',
		];
	}

}

<?php

namespace MediaWiki\Extension\Scribunto\Tests\Engines\LuaCommon;

/**
 * @covers \MediaWiki\Extension\Scribunto\Engines\LuaCommon\UriLibrary
 */
class UriLibraryTest extends LuaEngineTestBase {
	/** @inheritDoc */
	protected static $moduleName = 'UriLibraryTests';

	protected function setUp(): void {
		parent::setUp();

		$this->overrideConfigValues( [
			'Server' => '//wiki.local',
			'CanonicalServer' => 'http://wiki.local',
			'UsePathInfo' => true,
			'ActionPaths' => [],
			'Script' => '/w/index.php',
			'ScriptPath' => '/w',
			'ArticlePath' => '/wiki/$1',
			'FragmentMode' => [ 'legacy', 'html5' ],
		] );
	}

	protected function getTestModules() {
		return parent::getTestModules() + [
			'UriLibraryTests' => __DIR__ . '/UriLibraryTests.lua',
		];
	}
}

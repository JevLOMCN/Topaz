<?php

namespace MediaWiki\Extension\Scribunto\Tests\Engines\LuaSandbox;

use MediaWiki\Extension\Scribunto\Engines\LuaSandbox\LuaSandboxEngine;
use MediaWiki\Extension\Scribunto\Engines\LuaSandbox\LuaSandboxInterpreter;
use MediaWiki\Extension\Scribunto\Tests\Engines\LuaCommon\LuaInterpreterTestBase;

if ( !wfIsCLI() ) {
	exit;
}

/**
 * @group Lua
 * @group LuaSandbox
 * @covers \MediaWiki\Extension\Scribunto\Engines\LuaSandbox\LuaSandboxInterpreter
 */
class SandboxInterpreterTest extends LuaInterpreterTestBase {
	/** @var array */
	public $stdOpts = [
		'memoryLimit' => 50000000,
		'cpuLimit' => 30,
	];

	protected function newInterpreter( $opts = [] ) {
		$opts += $this->stdOpts;
		$engine = new LuaSandboxEngine( $this->stdOpts );
		return new LuaSandboxInterpreter( $engine, $opts );
	}

	public function testGetMemoryUsage() {
		$interpreter = $this->newInterpreter();
		$chunk = $interpreter->loadString( 's = string.rep("x", 1000000)', 'mem' );
		$interpreter->callFunction( $chunk );
		$mem = $interpreter->getPeakMemoryUsage();
		$this->assertGreaterThan( 1000000, $mem, 'memory usage' );
		$this->assertLessThan( 10000000, $mem, 'memory usage' );
	}
}

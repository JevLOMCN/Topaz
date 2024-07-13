<?php

namespace MediaWiki\Extension\Nuke\Test\Unit;

use MediaWiki\Extension\Nuke\Hooks\NukeHookRunner;
use MediaWiki\Tests\HookContainer\HookRunnerTestBase;

/**
 * @covers \MediaWiki\Extension\Nuke\Hooks\NukeHookRunner
 */
class HookRunnerTest extends HookRunnerTestBase {

	public static function provideHookRunners() {
		yield NukeHookRunner::class => [ NukeHookRunner::class ];
	}
}

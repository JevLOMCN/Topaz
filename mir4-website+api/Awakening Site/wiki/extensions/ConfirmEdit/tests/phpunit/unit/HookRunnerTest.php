<?php

namespace MediaWiki\Extension\ConfirmEdit\Test\Unit;

use MediaWiki\Extension\ConfirmEdit\Hooks\HookRunner;
use MediaWiki\Tests\HookContainer\HookRunnerTestBase;

/**
 * @covers \MediaWiki\Extension\ConfirmEdit\Hooks\HookRunner
 */
class HookRunnerTest extends HookRunnerTestBase {

	public static function provideHookRunners() {
		yield HookRunner::class => [ HookRunner::class ];
	}
}

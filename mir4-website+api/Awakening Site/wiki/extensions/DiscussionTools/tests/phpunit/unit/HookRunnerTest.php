<?php

namespace MediaWiki\Extension\DiscussionTools\Tests\Unit;

use MediaWiki\Extension\DiscussionTools\Hooks\HookRunner;
use MediaWiki\Tests\HookContainer\HookRunnerTestBase;

/**
 * @covers \MediaWiki\Extension\DiscussionTools\Hooks\HookRunner
 */
class HookRunnerTest extends HookRunnerTestBase {

	public static function provideHookRunners() {
		yield HookRunner::class => [ HookRunner::class ];
	}
}

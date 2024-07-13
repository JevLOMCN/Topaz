/**
 * Hooks
 *
 * Hooks are used for setup and teardown of the environment before and after each scenario.
 * It's preferable to use tags to invoke hooks rather than using the generic 'Before' and 'After'
 * events, which execute before and after all scenario.
 * https://github.com/cucumber/cucumber-js/blob/master/docs/support_files/hooks.md
 */

'use strict';

const { After, Before } = require( '@cucumber/cucumber' );

Before( function () {
	// This hook will be executed before ALL scenarios
} );

After( function () {
	// This hook will be executed after ALL scenarios
} );

Before( { tags: '@foo' }, function () {
	// This hook will be executed before scenarios tagged with @foo
} );

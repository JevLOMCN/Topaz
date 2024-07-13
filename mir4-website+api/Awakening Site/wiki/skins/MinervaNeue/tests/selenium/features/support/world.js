/**
 * World
 *
 * World is a function that is bound as `this` to each step of a scenario.
 * It is reset for each scenario.
 * https://github.com/cucumber/cucumber-js/blob/master/docs/support_files/world.md
 *
 * Contrary to Cucumber.js best practices, this `MinervaWorld` is not being
 * bound to scenarios with the `setWorldConstructor` like this:
 *
 * setWorldConstructor(MinervaWorld);
 *
 * Instead, it acts as a simple function that encapsulates all dependencies,
 * and is exported so that it can be imported into each step definition file,
 * allowing us to use the dependencies across scenarios.
 */

'use strict';

const mwCorePages = require( '../support/pages/mw_core_pages' ),
	minervaPages = require( '../support/pages/minerva_pages' );

function MinervaWorld() {
	/* pageObjects */
	Object.assign( this, mwCorePages );
	Object.assign( this, minervaPages );
}

module.exports = new MinervaWorld();

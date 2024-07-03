'use strict';

// Register prfun's Promises with node-pn
var Promise = require('./lib/utils/promise.js');
require('pn/_promise')(Promise); // This only needs to be done once.

// Comments below annotate the highest lts version of node for which the
// polyfills are necessary.  Remove when that version is no longer supported.

// v6
require('core-js/fn/object/entries');
require('core-js/fn/string/pad-start');
require('core-js/fn/string/pad-end');

// In Node v10, console.assert() was changed to log messages to stderr
// *WITHOUT THROWING AN EXCEPTION*.  We should clearly have been using
// a proper assertion library... but since we're switching to PHP anyway,
// for the moment just hack console.assert() to make things behave the
// way they used to.
if (require('semver').gte(process.version, '10.0.0')) {
	const oldAssert = console.assert;
	console.assert = function(value) {
		const args = Array.from(arguments);
		oldAssert.apply(console, args);
		if (!args[0]) {
			// We only get here in Node >= 0.10!
			args.shift();
			let msg = 'AssertionError';
			if (args.length) {
				const util = require('util');
				msg += ': ' + util.format.apply(util, args);
			}
			class AssertionException extends Error {
				constructor(msg2) { super(msg2); this.message = msg2; }
			}
			throw new AssertionException(msg);
		}
	};
}

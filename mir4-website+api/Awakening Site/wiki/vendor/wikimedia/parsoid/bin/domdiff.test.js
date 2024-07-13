#!/usr/bin/env node

'use strict';

require('../core-upgrade.js');

var DOMDiff = require('../lib/html2wt/DOMDiff.js').DOMDiff;
var ScriptUtils = require('../tools/ScriptUtils.js').ScriptUtils;
var ContentUtils = require('../lib/utils/ContentUtils.js').ContentUtils;
var ParsoidLogger = require('../lib/logger/ParsoidLogger.js').ParsoidLogger;
var MockEnv = require('../tests/MockEnv.js').MockEnv;
var Promise = require('../lib/utils/promise.js');
var yargs = require('yargs');
var fs = require('pn/fs');

var opts = yargs
.usage("Usage: $0 [options] [old-html-file new-html-file]\n\nProvide either inline html OR 2 files")
.options({
	help: {
		description: 'Show this message',
		'boolean': true,
		'default': false,
	},
	oldhtml: {
		description: 'Old html',
		'boolean': false,
		'default': null,
	},
	newhtml: {
		description: 'New html',
		'boolean': false,
		'default': null,
	},
	quiet: {
		description: 'Emit only the marked-up HTML',
		'boolean': true,
		'default': false,
	},
	debug: {
		description: 'Debug mode',
		'boolean': true,
		'default': false,
	},
});

Promise.async(function *() {
	var argv = opts.argv;
	var oldhtml = argv.oldhtml;
	var newhtml = argv.newhtml;

	if (!oldhtml && argv._[0]) {
		oldhtml = yield fs.readFile(argv._[0], 'utf8');
		newhtml = yield fs.readFile(argv._[1], 'utf8');
	}

	if (ScriptUtils.booleanOption(argv.help) || !oldhtml || !newhtml) {
		opts.showHelp();
		return;
	}

	const dummyEnv = new MockEnv({
		debug: ScriptUtils.booleanOption(argv.debug),
	}, null);

	// FIXME: Move to `MockEnv`
	if (argv.debug) {
		var logger = new ParsoidLogger(dummyEnv);
		logger.registerBackend(/^(trace|debug)(\/|$)/, logger.getDefaultTracerBackend());
		dummyEnv.log = (...args) => logger.log(...args);
	} else {
		dummyEnv.log = function() {};
	}

	var oldDOM = ContentUtils.ppToDOM(dummyEnv, oldhtml, { markNew: true });
	var newDOM = ContentUtils.ppToDOM(dummyEnv, newhtml, { markNew: true });

	ContentUtils.stripSectionTagsAndFallbackIds(oldDOM);
	ContentUtils.stripSectionTagsAndFallbackIds(newDOM);

	(new DOMDiff(dummyEnv)).diff(oldDOM, newDOM);

	ContentUtils.dumpDOM(newDOM, 'DIFF-marked DOM', {
		quiet: !!ScriptUtils.booleanOption(argv.quiet),
		storeDiffMark: true,
		env: dummyEnv,
	});

	process.exit(0);
})().done();

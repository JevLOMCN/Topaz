#!/usr/bin/env node

'use strict';

require('../core-upgrade.js');

var DOMNormalizer = require('../lib/html2wt/DOMNormalizer.js').DOMNormalizer;
var ContentUtils = require('../lib/utils/ContentUtils.js').ContentUtils;
var Promise = require('../lib/utils/promise.js');
var ScriptUtils = require('../tools/ScriptUtils.js').ScriptUtils;
var MockEnv = require('../tests/MockEnv.js').MockEnv;

var yargs = require('yargs');
var fs = require('pn/fs');

var opts = yargs
.usage("Usage: $0 [options] [html-file]\n\nProvide either inline html OR 1 file")
.options({
	help: {
		description: 'Show this message',
		'boolean': true,
		'default': false,
	},
	enableSelserMode: {
		description: [
			'Run in selser mode (but dom-diff markers are not loaded).',
			'This just forces more normalization code to run.',
			'So, this is "fake selser" mode till we are able to load diff markers from attributes'
		].join(' '),
		'boolean': true,
		'default': false,
	},
	html: {
		description: 'html',
		'boolean': false,
		'default': '',
	},
});

Promise.async(function *() {
	var argv = opts.argv;
	var html = argv.html;
	if (!html && argv._[0]) {
		html = yield fs.readFile(argv._[0], 'utf8');
	}

	if (ScriptUtils.booleanOption(argv.help) || !html) {
		opts.showHelp();
		return;
	}

	const env = new MockEnv({}, null);

	var mockState = {
		env,
		selserMode: argv.enableSelserMode
	};

	const domBody = ContentUtils.ppToDOM(env, html, { markNew: true });
	const normalizedBody = (new DOMNormalizer(mockState).normalize(domBody));

	ContentUtils.dumpDOM(normalizedBody, 'Normalized DOM', { env: mockState.env, storeDiffMark: true });

	process.exit(0);
})().done();

'use strict';

const fs = require('fs');
const path = require('path');

/* This is a wrapper around the roundtrip testing script */
const rtTest = require('../../bin/roundtrip-test.js');
const yaml = require('js-yaml');

// Read ids from a file and return the first line of the file
function getTestRunId(opts) {
	const testRunIdFile = opts.testRunIdFile || path.resolve(__dirname, 'parsoid.rt-test.ids');
	return fs.readFileSync(testRunIdFile, 'utf-8').split('\n')[0];
}

function runRoundTripTest(config, test) {
	return rtTest.runTests(test.title, {
		prefix: test.prefix,
		parsoidURLOpts: config.parsoidPHP,
	}, rtTest.xmlFormat).then(function(result) {
		return result.output;
	});
}

if (typeof module === 'object') {
	module.exports.runRoundTripTest = runRoundTripTest;
	module.exports.getTestRunId = getTestRunId;
}

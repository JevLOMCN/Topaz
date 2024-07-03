'use strict';

const { config } = require( './wdio.conf' );

config.specs = [ __dirname + '/features/*.feature' ];
config.framework = 'cucumber';
config.cucumberOpts = {
	require: [
		'./tests/selenium/features/support/*.js',
		'./tests/selenium/features/step_definitions/index.js'
		// search a (sub)folder for JS files with a wildcard
		// works since version 1.1 of the wdio-cucumber-framework
		// './src/**/*.js',
	]
};
exports.config = config;

'use strict';

const createScreenshotEnvironment = require( './screenshots.js' ).createScreenshotEnvironment,
	test = require( 'selenium-webdriver/testing' ),
	diffs = require( './screenshots-client/diffs.js' ),
	runScreenshotTest = createScreenshotEnvironment( test );

function runTests( lang ) {

	const runLang = runScreenshotTest.bind( this, lang );

	test.describe( 'Screenshots: ' + lang, function () {
		this.lang = lang;
		test.it( 'Simple diff', function () {
			runLang( 'VisualEditor_diff_simple', diffs.simple );
			runLang( 'VisualEditor_diff_move_and_change', diffs.moveAndChange );
			runLang( 'VisualEditor_diff_link_change', diffs.linkChange );
			runLang( 'VisualEditor_diff_list_change', diffs.listChange );
		} );
	} );
}

for ( let i = 0, l = langs.length; i < l; i++ ) {
	runTests( langs[ i ] );
}

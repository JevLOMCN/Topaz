'use strict';

const createScreenshotEnvironment = require( './screenshots.js' ).createScreenshotEnvironment,
	test = require( 'selenium-webdriver/testing' ),
	userGuide = require( './screenshots-client/userGuide.js' ),
	runScreenshotTest = createScreenshotEnvironment( test );

function runTests( lang ) {

	const runLang = runScreenshotTest.bind( this, lang );

	test.describe( 'Screenshots: ' + lang, function () {
		this.lang = lang;
		test.it( 'Screenshots', function () {

			// Toolbar & action tools
			runLang( 'VisualEditor_toolbar', userGuide.toolbar, 0 );
			runLang( 'VisualEditor_toolbar_actions', userGuide.toolbarActions, 0 );

			// Citoid inspector
			runLang( 'VisualEditor_Citoid_Inspector', userGuide.citoidInspector );
			runLang( 'VisualEditor_Citoid_Inspector_Manual', userGuide.citoidInspectorManual );
			runLang( 'VisualEditor_Citoid_Inspector_Reuse', userGuide.citoidInspectorReuse, undefined, userGuide.citoidInspectorTeardown );

			// Tool groups (headings/text style/indentation/insert/page settings)
			runLang( 'VisualEditor_Toolbar_Headings', userGuide.toolbarHeadings );
			runLang( 'VisualEditor_Toolbar_Formatting', userGuide.toolbarFormatting );
			runLang( 'VisualEditor_Toolbar_Lists_and_indentation', userGuide.toolbarLists );
			runLang( 'VisualEditor_Insert_Menu', userGuide.toolbarInsert );
			runLang( 'VisualEditor_Media_Insert_Menu', userGuide.toolbarMedia );
			runLang( 'VisualEditor_Template_Insert_Menu', userGuide.toolbarTemplate );
			runLang( 'VisualEditor_insert_table', userGuide.toolbarTable );
			runLang( 'VisualEditor_Formula_Insert_Menu', userGuide.toolbarFormula );
			runLang( 'VisualEditor_References_List_Insert_Menu', userGuide.toolbarReferences );
			runLang( 'VisualEditor_More_Settings', userGuide.toolbarSettings );
			runLang( 'VisualEditor_page_settings_item', userGuide.toolbarPageSettings );
			runLang( 'VisualEditor_category_item', userGuide.toolbarCategory, undefined, userGuide.toolbarTeardown );

			// Save dialog
			runLang( 'VisualEditor_save_dialog', userGuide.save, undefined, userGuide.saveTeardown );

			// Special character inserter
			runLang( 'VisualEditor_Toolbar_SpecialCharacters', userGuide.specialCharacters, undefined, userGuide.specialCharactersTeardown );

			// Math dialog
			runLang( 'VisualEditor_formula', userGuide.formula, undefined, userGuide.formulaTeardown );

			// Reference list dialog
			runLang( 'VisualEditor_references_list', userGuide.referenceList, undefined, userGuide.referenceListTeardown );

			// Cite button
			runLang( 'VisualEditor_citoid_Cite_button', userGuide.toolbarCite, undefined, userGuide.toolbarCiteTeardown );

			// Link inspector
			runLang( 'VisualEditor-link_tool-search_results', userGuide.linkSearchResults, undefined, userGuide.linkSearchResultsTeardown );
		} );
	} );
}

langs.forEach( function ( lang ) {
	runTests( lang );
} );

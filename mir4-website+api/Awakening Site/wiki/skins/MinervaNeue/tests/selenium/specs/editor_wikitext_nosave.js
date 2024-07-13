'use strict';

const {
		iAmOnAPageThatDoesNotExist, iClickTheBrowserBackButton,
		iClickTheOverlayCloseButton, iDoNotSeeAnOverlay,
		iAmLoggedIntoTheMobileWebsite
	} = require( '../features/step_definitions/common_steps' ),
	{
		iClickTheEditButton, iSeeTheWikitextEditorOverlay
	} = require( '../features/step_definitions/editor_steps' );

// @chrome @en.m.wikipedia.beta.wmflabs.org @firefox @test2.m.wikipedia.org @vagrant @login
describe( 'Wikitext Editor', () => {

	beforeEach( async () => {
		await iAmLoggedIntoTheMobileWebsite();
		await iAmOnAPageThatDoesNotExist();
	} );

	// @smoke
	// Skipped on 2022-12-07 because of T313775
	it.skip( 'Closing editor (overlay button)', () => {
		iClickTheEditButton();
		iSeeTheWikitextEditorOverlay();
		iClickTheOverlayCloseButton();
		iDoNotSeeAnOverlay();
	} );

	// FIXME T348539
	it.skip( 'Closing editor (browser button)', async () => {
		await iClickTheEditButton();
		await iSeeTheWikitextEditorOverlay();
		await iClickTheBrowserBackButton();
		await iDoNotSeeAnOverlay();
	} );

} );

'use strict';

const { iAmViewingAnUnwatchedPage } = require( '../features/step_definitions/create_page_api_steps' ),
	{
		iShouldSeeAToastNotificationWithMessage,
		iAmLoggedIntoTheMobileWebsite
	} = require( '../features/step_definitions/common_steps' ),
	{
		theWatchstarShouldBeSelected,
		iClickTheWatchstar } = require( '../features/step_definitions/watch_steps' );

// @chrome @smoke @test2.m.wikipedia.org @login @vagrant
describe( 'Manage Watchlist', () => {

	beforeEach( async () => {
		await iAmLoggedIntoTheMobileWebsite();
	} );

	it( 'Add an article to the watchlist', async () => {
		await iAmViewingAnUnwatchedPage();
		await iClickTheWatchstar();
		await iShouldSeeAToastNotificationWithMessage( 'added' );
		await theWatchstarShouldBeSelected();
	} );
} );

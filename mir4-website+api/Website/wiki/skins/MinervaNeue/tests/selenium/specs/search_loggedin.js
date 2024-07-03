'use strict';

const {
		pageExists, iShouldSeeAToastNotification,
		iAmUsingMobileScreenResolution,
		iAmUsingTheMobileSite,
		iAmLoggedIntoTheMobileWebsite,
		iAmOnPage
	} = require( '../features/step_definitions/common_steps' ),
	{
		iClickTheSearchIcon,
		iTypeIntoTheSearchBox,
		iClickASearchWatchstar,
		iSeeTheSearchOverlay
	} = require( '../features/step_definitions/search_steps' );

// @test2.m.wikipedia.org @vagrant @login
describe( 'Search', () => {
	// FIXME: T334634
	it.skip( 'Clicking on a watchstar toggles the watchstar', async () => {
		await iAmUsingTheMobileSite();
		await pageExists( 'Selenium search test' );
		await iAmLoggedIntoTheMobileWebsite();
		await iAmOnPage( 'Main Page' );
		await iAmUsingMobileScreenResolution();
		await iClickTheSearchIcon();
		await iSeeTheSearchOverlay();
		await iTypeIntoTheSearchBox( 'Selenium search tes' );
		// This pause statement is a temporary bandaid until we figure a bettery dynamic sync
		// eslint-disable-next-line wdio/no-pause
		await browser.pause( 1000 );
		await iClickASearchWatchstar();
		await iShouldSeeAToastNotification();
	} );
} );

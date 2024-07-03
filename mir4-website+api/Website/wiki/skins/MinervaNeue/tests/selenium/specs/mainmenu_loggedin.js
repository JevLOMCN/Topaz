'use strict';

const {
		iAmLoggedIntoTheMobileWebsite,
		iAmOnPage
	} = require( '../features/step_definitions/common_steps' ),
	{ iSeeALinkToAboutPage, iShouldSeeAUserPageLinkInMenu,
		iShouldSeeLogoutLinkInMenu,
		iClickOnTheMainNavigationButton,
		iShouldSeeALinkInMenu, iShouldSeeALinkToDisclaimer
	} = require( '../features/step_definitions/menu_steps' );

// @chrome @en.m.wikipedia.beta.wmflabs.org @firefox @test2.m.wikipedia.org @vagrant @login
describe( 'Menus open correct page for anonymous users', () => {

	beforeEach( async () => {
		await iAmLoggedIntoTheMobileWebsite();
		await iAmOnPage( 'Main Page' );
	} );

	it( 'Check links in menu', async () => {
		await iClickOnTheMainNavigationButton();
		await iShouldSeeALinkToDisclaimer();
		await iShouldSeeAUserPageLinkInMenu();
		await iSeeALinkToAboutPage();
		[ 'Home', 'Random', 'Settings', 'Watchlist' ].forEach( async ( label ) => {
			await iShouldSeeALinkInMenu( label );
		} );
		await iShouldSeeLogoutLinkInMenu();
		try {
			await iShouldSeeALinkInMenu( 'Nearby' );
		} catch ( e ) {
			console.warn( 'Nearby item will only appear in main menu if $wgMFNearby is configured' );
		}
	} );
} );

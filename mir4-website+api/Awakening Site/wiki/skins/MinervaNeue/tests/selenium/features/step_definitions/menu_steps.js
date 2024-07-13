'use strict';

const assert = require( 'assert' );
const { ArticlePage } = require( '../support/world.js' );

const iSeeALinkToAboutPage = async () => {
	assert.strictEqual( await ArticlePage.menu_element.$( '*=About' ).isDisplayed(), true );
};

const iClickOnTheMainNavigationButton = async () => {
	await ArticlePage.menu_button_element.waitForDisplayed();
	await ArticlePage.menu_button_element.click();
};

const iShouldSeeAUserPageLinkInMenu = async () => {
	await ArticlePage.menu_element.$( '.menu__item--user' );
};

const iShouldSeeLogoutLinkInMenu = async () => {
	await ArticlePage.menu_element.$( '.menu__item--logout' );
};

const iShouldSeeALinkInMenu = async ( text ) => {
	assert.strictEqual( await ArticlePage.menu_element.$( `span=${ text }` ).isDisplayed(),
		true, `Link to ${ text } is visible.` );
};

const iShouldSeeALinkToDisclaimer = async () => {
	await ArticlePage.menu_element.$( 'span=Disclaimers' ).waitForDisplayed();
	assert.strictEqual( await ArticlePage.menu_element.$( 'span=Disclaimers' ).isDisplayed(), true );
};

module.exports = {
	iClickOnTheMainNavigationButton,
	iSeeALinkToAboutPage, iShouldSeeAUserPageLinkInMenu,
	iShouldSeeLogoutLinkInMenu,
	iShouldSeeALinkInMenu, iShouldSeeALinkToDisclaimer
};

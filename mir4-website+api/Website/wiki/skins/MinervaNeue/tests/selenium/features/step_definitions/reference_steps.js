'use strict';

const { ArticlePage } = require( './../support/world' );

const iClickOnTheMask = () => {
	ArticlePage.drawer_mask_element.waitForDisplayed();
	ArticlePage.drawer_mask_element.click();
};

const iShouldSeeNotTheReferenceDrawer = () => {
	browser.waitUntil( () => !ArticlePage.drawer_element.isDisplayed() );
};

const iClickOnAReference = () => {
	ArticlePage.reference_element.waitForDisplayed();
	ArticlePage.reference_element.click();
};

const iClickOnANestedReference = () => {
	ArticlePage.drawer_reference_element.waitForDisplayed();
	ArticlePage.drawer_reference_element.click();
};

const iShouldSeeDrawerWithText = ( text ) => {
	ArticlePage.drawer_element.waitForDisplayed();
	browser.waitUntil( () => ArticlePage.drawer_element.getText().includes( text ) );
};

module.exports = {
	iClickOnAReference,
	iClickOnTheMask,
	iShouldSeeNotTheReferenceDrawer,
	iClickOnANestedReference,
	iShouldSeeDrawerWithText
};

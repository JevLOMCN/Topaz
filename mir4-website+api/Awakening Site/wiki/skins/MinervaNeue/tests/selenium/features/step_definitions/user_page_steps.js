'use strict';

const assert = require( 'assert' );
const { ArticlePage } = require( '../support/world.js' );
const { iAmOnPage } = require( './common_steps' );
const { theTextOfTheFirstHeadingShouldBe } = require( './editor_steps' );

const username = browser.config.mwUser.replace( /_/g, ' ' );

const iVisitMyUserPage = async () => {
	await iAmOnPage( `User:${ username }` );
};

const iShouldBeOnMyUserPage = async () => {
	await theTextOfTheFirstHeadingShouldBe( username );
};

const thereShouldBeALinkToMyContributions = async () => {
	assert.strictEqual( await ArticlePage.contributions_link_element.isDisplayed(), true );
};
const thereShouldBeALinkToMyTalkPage = async () => {
	assert.strictEqual( await ArticlePage.talk_tab_element.isDisplayed(), true );
};

module.exports = { iVisitMyUserPage, iShouldBeOnMyUserPage,
	thereShouldBeALinkToMyContributions, thereShouldBeALinkToMyTalkPage };

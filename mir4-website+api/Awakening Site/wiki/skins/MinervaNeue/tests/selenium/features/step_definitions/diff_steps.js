'use strict';

const assert = require( 'assert' ),
	{ SpecialMobileDiffPage } = require( '../support/world.js' );

const iShouldSeeAddedContent = ( text ) => {
	SpecialMobileDiffPage.inserted_content_element.waitForDisplayed();
	assert.strictEqual( SpecialMobileDiffPage.inserted_content_element.getText(), text );
};
const iShouldSeeRemovedContent = ( text ) => {
	assert.strictEqual( SpecialMobileDiffPage.deleted_content_element.getText(), text );
};

module.exports = { iShouldSeeAddedContent, iShouldSeeRemovedContent };

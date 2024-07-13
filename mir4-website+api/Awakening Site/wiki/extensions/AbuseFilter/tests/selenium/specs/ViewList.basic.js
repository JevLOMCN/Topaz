'use strict';

const assert = require( 'assert' ),
	LoginPage = require( 'wdio-mediawiki/LoginPage' ),
	ViewListPage = require( '../pageobjects/viewlist.page' );

describe( 'Special:AbuseFilter', function () {
	it( 'page should exist on installation', async function () {
		await ViewListPage.open();
		assert.equal( await ViewListPage.title.getText(), 'Abuse filter management' );
	} );
	it( 'page should have the button for creating a new filter', async function () {
		await LoginPage.loginAdmin();
		await ViewListPage.open();
		assert.equal( await ViewListPage.newFilterButton.getText(), 'Create a new filter' );
		const newFilterButton = await ViewListPage.newFilterButton.getAttribute( 'href' );
		assert.notEqual(
			( newFilterButton.indexOf( 'Special:AbuseFilter/new' ) ),
			-1
		);
	} );
} );

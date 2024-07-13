'use strict';
const assert = require( 'assert' );
const EditPage = require( '../pageobjects/edit.page' );
const LoginPage = require( 'wdio-mediawiki/LoginPage' );
const Util = require( 'wdio-mediawiki/Util' );

describe( 'Content Editable', function () {

	let name, content;

	beforeEach( async function () {
		content = Util.getTestString();
		name = Util.getTestString();
		await LoginPage.loginAdmin();

		await EditPage.openForEditing( name );
		await EditPage.toolbar.waitForDisplayed( { timeout: 20000 } );
	} );

	afterEach( async function () {
		// T269566: Popup with text
		// 'Leave site? Changes that you made may not be saved. Cancel/Leave'
		// appears after the browser tries to leave the page with the preview.
		await browser.reloadSession();
	} );

	it( 'should load when an url is opened @daily', async function () {
		assert( await EditPage.toolbar.isDisplayed() );
	} );

	it( 'should be editable', async function () {
		await EditPage.veRootNode.setValue( content );

		assert.equal( await EditPage.veRootNode.getText(), content );
	} );

	it( 'should save an edit', async function () {
		await EditPage.veRootNode.setValue( content );
		await EditPage.savePageDots.click();
		await EditPage.savePage.waitForClickable();
		await EditPage.savePage.click();

		await EditPage.saveComplete();
		assert.strictEqual( await EditPage.notification.getText(), 'The page has been created.' );
	} );

	it( 'should insert a table', async function () {
		await EditPage.insertTable();

		assert( await EditPage.insertedTable.isDisplayed() );
	} );

} );

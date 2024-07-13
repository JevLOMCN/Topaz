'use strict';
const assert = require( 'assert' );
const EditPage = require( '../pageobjects/edit.page' );
const Util = require( 'wdio-mediawiki/Util' );

describe( 'Toolbar', function () {

	let name;

	beforeEach( async function () {
		name = Util.getTestString();
		await EditPage.openForEditing( name );
		await EditPage.toolbar.waitForDisplayed( { timeout: 20000 } );
	} );

	afterEach( async function () {
		// T269566: Popup with text
		// 'Leave site? Changes that you made may not be saved. Cancel/Leave'
		// appears after the browser tries to leave the page with the preview.
		await browser.reloadSession();
	} );

	it( 'should open notices popup as soon as it loads', async function () {
		assert( await EditPage.notices.isDisplayed() );
	} );

	it( 'should open switch editor', async function () {
		await EditPage.switchEditorElement.click();

		assert( await EditPage.visualEditing.isDisplayed() );
	} );

	it( 'should open page options', async function () {
		await EditPage.pageOptionsElement.click();

		assert( await EditPage.options.isDisplayed() );
	} );

	it( 'should open help popup', async function () {
		await EditPage.helpElement.click();

		assert( await EditPage.helpPopup.isDisplayed() );
	} );

	it.skip( 'should open special characters menu', async function () {
		await EditPage.specialCharacterElement.click();
		await EditPage.specialCharacterMenu.waitForDisplayed( { timeout: 1000 } );

		assert( await EditPage.specialCharacterMenu.isDisplayed() );
	} );

	it( 'should open insert menu', async function () {
		await EditPage.insert.click();

		assert( await EditPage.insertMenu.isDisplayed() );
	} );

	it( 'should open structure options menu', async function () {
		await EditPage.structureOptionsElement.click();

		assert( await EditPage.bulletListOption.isDisplayed() );
	} );

	it( 'should open style text options', async function () {
		await EditPage.styleTextElement.click();

		assert( await EditPage.boldTextStyleOption.isDisplayed() );
	} );

	it( 'should open format paragraph menu', async function () {
		await EditPage.formatParagraphElement.click();

		assert( await EditPage.paragraphFormatMenu.isDisplayed() );
	} );

} );

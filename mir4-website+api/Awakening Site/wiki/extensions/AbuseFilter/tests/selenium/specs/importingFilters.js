'use strict';

const assert = require( 'assert' ),
	LoginPage = require( 'wdio-mediawiki/LoginPage' ),
	ViewEditPage = require( '../pageobjects/viewedit.page' ),
	ViewListPage = require( '../pageobjects/viewlist.page' ),
	ViewImportPage = require( '../pageobjects/viewimport.page' );

describe( 'When importing a filter', function () {
	const filterSpecs = {
		name: 'My filter name',
		comments: 'Notes go here.',
		rules: 'true === false',
		enabled: true,
		hidden: true,
		deleted: false,
		warnMessage: 'abusefilter-warning-foobar'
	};
	let importData;

	before( async function () {
		await LoginPage.loginAdmin();

		await ViewEditPage.open( 'new' );
		await ViewEditPage.switchEditor();
		await ViewEditPage.name.setValue( filterSpecs.name );
		await ViewEditPage.rules.setValue( filterSpecs.rules );
		await ViewEditPage.comments.setValue( filterSpecs.comments );
		if ( !filterSpecs.enabled ) {
			await ViewEditPage.enabled.click();
		}
		if ( filterSpecs.hidden ) {
			await ViewEditPage.hidden.click();
		}
		if ( filterSpecs.deleted ) {
			await ViewEditPage.deleted.click();
		}
		await ViewEditPage.warnCheckbox.click();
		await ViewEditPage.setWarningMessage( filterSpecs.warnMessage );
		await ViewEditPage.submit();

		assert( await ViewListPage.filterSavedNotice.isDisplayed() );
		const filterID = await ViewListPage.savedFilterID();
		await ViewEditPage.open( filterID );
		importData = await ViewEditPage.exportData;
	} );

	it( 'the interface should be visible', async function () {
		await ViewImportPage.open();
		assert( await ViewImportPage.importData.isDisplayed() );
	} );

	it( 'it should redirect to ViewEdit after submission', async function () {
		await ViewImportPage.importText( 'SOME INVALID GIBBERISH' );
		assert( /\/new$/.test( await browser.getUrl() ) );
	} );

	it( 'bad data results in an error', async function () {
		assert( await ViewEditPage.error.isDisplayed() );
	} );

	it( 'valid data shows the editing interface', async function () {
		await ViewImportPage.open();
		await ViewImportPage.importText( importData );
		assert( await ViewEditPage.name.isDisplayed() );
	} );

	describe( 'Data on the editing interface is correct', function () {
		it( 'filter specs are copied', async function () {
			assert.strictEqual( await ViewEditPage.name.getValue(), filterSpecs.name );
			assert.strictEqual( await ViewEditPage.comments.getValue(), filterSpecs.comments + '\n' );
			assert.strictEqual( await ViewEditPage.rules.getValue(), filterSpecs.rules + '\n' );
		} );
		it( 'filter flags are copied', async function () {
			assert.strictEqual( await ViewEditPage.enabled.isSelected(), !!filterSpecs.enabled );
			assert.strictEqual( await ViewEditPage.hidden.isSelected(), !!filterSpecs.hidden );
			assert.strictEqual( await ViewEditPage.deleted.isSelected(), !!filterSpecs.deleted );
		} );
		it( 'filter actions are copied', async function () {
			assert.strictEqual( await ViewEditPage.warnCheckbox.isSelected(), true );
			assert.strictEqual(
				await ViewEditPage.warnOtherMessage.getValue(),
				filterSpecs.warnMessage
			);
		} );

		it( 'the imported data can be saved', async function () {
			await ViewEditPage.submit();
			const filterNotice = await ViewListPage.filterSavedNotice;
			assert( filterNotice.isDisplayed() );
		} );
	} );
} );

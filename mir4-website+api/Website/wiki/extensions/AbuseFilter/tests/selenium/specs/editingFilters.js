'use strict';

const assert = require( 'assert' ),
	LoginPage = require( 'wdio-mediawiki/LoginPage' ),
	ViewEditPage = require( '../pageobjects/viewedit.page' ),
	ViewListPage = require( '../pageobjects/viewlist.page' );

describe( 'Filter editing', function () {
	describe( 'The editing interface', function () {
		it( 'is not visible to logged-out users', async function () {
			await ViewEditPage.open( 'new' );
			assert( await ViewEditPage.error.isDisplayed() );
		} );

		it( 'is visible to logged-in admins', async function () {
			await LoginPage.loginAdmin();
			await ViewEditPage.open( 'new' );
			assert( await ViewEditPage.name.isDisplayed() );
		} );
	} );

	describe( 'Trying to open a non-existing filter', function () {
		before( async function () {
			await LoginPage.loginAdmin();
		} );

		it( 'I should receive an error', async function () {
			await ViewEditPage.open( 1234567 );
			assert( await ViewEditPage.error.isDisplayed() );
			assert.strictEqual( await ViewEditPage.error.getText(), 'The filter you specified does not exist' );
		} );
	} );

	const filterSpecs = {
		name: 'My test filter',
		rules: '"confirmed" in user_groups & true === false',
		comments: 'Some notes',
		warnMsg: 'abusefilter-warning-foobar'
	};
	let filterID, historyID;

	async function assertFirstVersionSaved() {
		assert.strictEqual( await ViewEditPage.name.getValue(), filterSpecs.name );
		assert.strictEqual( await ViewEditPage.rules.getValue(), filterSpecs.rules + '\n' );
		assert.strictEqual( await ViewEditPage.comments.getValue(), filterSpecs.comments + '\n' );
		await ViewEditPage.warnCheckbox.isSelected();
		assert.strictEqual( await ViewEditPage.warnOtherMessage.getValue(), filterSpecs.warnMsg );
	}

	describe( 'Creating a new filter', function () {
		before( async function () {
			await LoginPage.loginAdmin();
			await ViewEditPage.open( 'new' );
		} );

		it( 'edit can be saved (1)', async function () {
			await ViewEditPage.switchEditor();

			await ViewEditPage.name.setValue( filterSpecs.name );
			await ViewEditPage.rules.setValue( filterSpecs.rules );
			await ViewEditPage.comments.setValue( filterSpecs.comments );
			await ViewEditPage.warnCheckbox.click();
			await ViewEditPage.setWarningMessage( filterSpecs.warnMsg );
			await ViewEditPage.submit();

			assert( await ViewListPage.filterSavedNotice.isDisplayed() );

			filterID = await ViewListPage.savedFilterID();
			assert.ok( filterID );
			historyID = await ViewListPage.savedFilterHistoryID();
			assert.ok( historyID );
		} );

		it( 'saved data is retained (1)', async function () {
			await ViewEditPage.open( filterID );
			await assertFirstVersionSaved();
		} );
	} );

	describe( 'Editing an existing filter', function () {
		before( async function () {
			await LoginPage.loginAdmin();
			await ViewEditPage.open( filterID );
		} );

		const newName = 'New filter name',
			newNotes = 'More filter notes';

		it( 'edit can be saved (2)', async function () {
			await ViewEditPage.name.setValue( newName );
			await ViewEditPage.comments.addValue( newNotes );
			await ViewEditPage.submit();
			assert( await ViewListPage.filterSavedNotice.isDisplayed() );
		} );

		it( 'saved data is retained (2)', async function () {
			await ViewEditPage.open( filterID );
			assert.strictEqual( await ViewEditPage.name.getValue(), newName );
			assert.strictEqual( await ViewEditPage.comments.getValue(), newNotes + filterSpecs.comments + '\n' );
		} );
	} );

	describe( 'Restoring an old version of a filter', function () {
		before( async function () {
			await LoginPage.loginAdmin();
		} );

		it( 'edit can be saved (3)', async function () {
			await ViewEditPage.open( 'history/' + filterID + '/item/' + historyID );
			await ViewEditPage.submit();
			assert( await ViewListPage.filterSavedNotice.isDisplayed() );
		} );

		it( 'saved data is retained (3)', async function () {
			await ViewEditPage.open( filterID );
			await assertFirstVersionSaved();
		} );
	} );

	describe( 'CSRF protection', function () {
		before( async function () {
			await LoginPage.loginAdmin();
			await ViewEditPage.open( 'new' );
		} );

		const filterName = 'Testing CSRF';

		it( 'a CSRF token is required to save the filter', async function () {
			await ViewEditPage.invalidateToken();
			await ViewEditPage.name.setValue( filterName );
			await ViewEditPage.submit();
			assert( await ViewEditPage.warning.isDisplayed() );
		} );
		it( 'even if the token is invalid, the ongoing edit is not lost', async function () {
			assert.strictEqual( await ViewEditPage.name.getValue(), filterName );
		} );
	} );

	describe( 'Trying to save a filter with bad data', function () {
		before( async function () {
			await LoginPage.loginAdmin();
			await ViewEditPage.open( 'new' );
		} );

		it( 'cannot save an empty filter', async function () {
			await ViewEditPage.submit();
			assert( await ViewEditPage.error.isDisplayed() );
		} );

		const rules = 'action === "edit"';

		it( 'cannot save a filter with rules but no name', async function () {
			await ViewEditPage.switchEditor();
			await ViewEditPage.rules.setValue( rules );
			await ViewEditPage.submit();
			assert( await ViewEditPage.error.isDisplayed() );
		} );

		it.skip( 'data is retained if saving fails', async function () {
			const rulesValue = await ViewEditPage.rules.getValue();
			assert.strictEqual( rulesValue, rules + '\n' );
		} );
	} );
} );

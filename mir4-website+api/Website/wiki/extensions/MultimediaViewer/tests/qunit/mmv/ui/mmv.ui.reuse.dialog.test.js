/*
 * This file is part of the MediaWiki extension MultimediaViewer.
 *
 * MultimediaViewer is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * MultimediaViewer is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with MultimediaViewer.  If not, see <http://www.gnu.org/licenses/>.
 */

const { ReuseDialog, Repo } = require( 'mmv' );

( function () {
	function makeReuseDialog( sandbox ) {
		const $fixture = $( '#qunit-fixture' );
		const config = { getFromLocalStorage: sandbox.stub(), setInLocalStorage: sandbox.stub() };
		return new ReuseDialog( $fixture, $( '<div>' ).appendTo( $fixture ), config );
	}

	QUnit.module( 'mmv.ui.reuse.Dialog', QUnit.newMwEnvironment() );

	QUnit.test( 'Sense test, object creation and UI construction', function ( assert ) {
		const reuseDialog = makeReuseDialog( this.sandbox );

		assert.true( reuseDialog instanceof ReuseDialog, 'Reuse UI element is created.' );
		assert.strictEqual( reuseDialog.$dialog.length, 1, 'Reuse dialog div created.' );
	} );

	QUnit.test( 'handleOpenCloseClick():', function ( assert ) {
		const reuseDialog = makeReuseDialog( this.sandbox );

		reuseDialog.openDialog = function () {
			assert.true( true, 'openDialog called.' );
		};
		reuseDialog.closeDialog = function () {
			assert.true( false, 'closeDialog should not have been called.' );
		};

		// Dialog is closed by default, we should open it
		reuseDialog.handleOpenCloseClick();

		reuseDialog.openDialog = function () {
			assert.true( false, 'openDialog should not have been called.' );
		};
		reuseDialog.closeDialog = function () {
			assert.true( true, 'closeDialog called.' );
		};
		reuseDialog.isOpen = true;

		// Dialog open now, we should close it.
		reuseDialog.handleOpenCloseClick();
	} );

	QUnit.test( 'handleTabSelection():', function ( assert ) {
		const reuseDialog = makeReuseDialog( this.sandbox );

		reuseDialog.initTabs();

		// Share pane is selected
		reuseDialog.handleTabSelection( { getData: () => 'share' } );
		assert.strictEqual( reuseDialog.tabs.share.$pane.hasClass( 'active' ), true, 'Share tab shown.' );
		assert.strictEqual( reuseDialog.tabs.embed.$pane.hasClass( 'active' ), false, 'Embed tab hidden.' );
		assert.strictEqual( reuseDialog.config.setInLocalStorage.calledWith( 'mmv-lastUsedTab', 'share' ), true,
			'Tab state saved in local storage.' );

		// Embed pane is selected
		reuseDialog.handleTabSelection( { getData: () => 'embed' } );
		assert.strictEqual( reuseDialog.tabs.share.$pane.hasClass( 'active' ), false, 'Share tab hidden.' );
		assert.strictEqual( reuseDialog.tabs.embed.$pane.hasClass( 'active' ), true, 'Embed tab shown.' );
	} );

	QUnit.test( 'default tab:', function ( assert ) {
		let reuseDialog = makeReuseDialog( this.sandbox );
		reuseDialog.initTabs();
		assert.strictEqual( reuseDialog.selectedTab, 'share', 'Share tab is default' );

		reuseDialog = makeReuseDialog( this.sandbox );
		reuseDialog.config.getFromLocalStorage.withArgs( 'mmv-lastUsedTab' ).returns( 'share' );
		reuseDialog.initTabs();
		assert.strictEqual( reuseDialog.selectedTab, 'share', 'Default can be overridden' );
	} );

	QUnit.test( 'attach()/unattach():', function ( assert ) {
		const reuseDialog = makeReuseDialog( this.sandbox );

		reuseDialog.initTabs();

		reuseDialog.handleOpenCloseClick = function () {
			assert.true( false, 'handleOpenCloseClick should not have been called.' );
		};
		reuseDialog.handleTabSelection = function () {
			assert.true( false, 'handleTabSelection should not have been called.' );
		};

		// Triggering action events before attaching should do nothing
		$( document ).trigger( 'mmv-reuse-open' );
		reuseDialog.reuseTabs.emit( 'select' );

		reuseDialog.handleOpenCloseClick = function () {
			assert.true( true, 'handleOpenCloseClick called.' );
		};
		reuseDialog.handleTabSelection = function () {
			assert.true( true, 'handleTabSelection called.' );
		};

		reuseDialog.attach();

		// Action events should be handled now
		$( document ).trigger( 'mmv-reuse-open' );
		reuseDialog.reuseTabs.emit( 'select' );

		// Test the unattach part
		reuseDialog.handleOpenCloseClick = function () {
			assert.true( false, 'handleOpenCloseClick should not have been called.' );
		};
		reuseDialog.handleTabSelection = function () {
			assert.true( false, 'handleTabSelection should not have been called.' );
		};

		reuseDialog.unattach();

		// Triggering action events now that we are unattached should do nothing
		$( document ).trigger( 'mmv-reuse-open' );
		reuseDialog.reuseTabs.emit( 'select' );
	} );

	QUnit.test( 'start/stopListeningToOutsideClick():', function ( assert ) {
		const reuseDialog = makeReuseDialog( this.sandbox );
		const realCloseDialog = reuseDialog.closeDialog;

		reuseDialog.initTabs();

		function clickOutsideDialog() {
			const event = new $.Event( 'click', { target: reuseDialog.$container[ 0 ] } );
			reuseDialog.$container.trigger( event );
			return event;
		}
		function clickInsideDialog() {
			const event = new $.Event( 'click', { target: reuseDialog.$dialog[ 0 ] } );
			reuseDialog.$dialog.trigger( event );
			return event;
		}

		function assertDialogDoesNotCatchClicks() {
			reuseDialog.closeDialog = () => assert.true( false, 'Dialog is not affected by click' );
			const event = clickOutsideDialog();
			assert.strictEqual( event.isDefaultPrevented(), false, 'Dialog does not affect click' );
			assert.strictEqual( event.isPropagationStopped(), false, 'Dialog does not affect click propagation' );
		}
		function assertDialogCatchesOutsideClicksOnly() {
			reuseDialog.closeDialog = () => assert.true( false, 'Dialog is not affected by inside click' );
			let event = clickInsideDialog();
			assert.strictEqual( event.isDefaultPrevented(), false, 'Dialog does not affect inside click' );
			assert.strictEqual( event.isPropagationStopped(), false, 'Dialog does not affect inside click propagation' );
			reuseDialog.closeDialog = () => assert.true( true, 'Dialog is closed by outside click' );
			event = clickOutsideDialog();
			assert.strictEqual( event.isDefaultPrevented(), true, 'Dialog catches outside click' );
			assert.strictEqual( event.isPropagationStopped(), true, 'Dialog stops outside click propagation' );
		}

		assertDialogDoesNotCatchClicks();
		reuseDialog.openDialog();
		assertDialogCatchesOutsideClicksOnly();
		realCloseDialog.call( reuseDialog );
		assertDialogDoesNotCatchClicks();
		reuseDialog.openDialog();
		reuseDialog.unattach();
		assertDialogDoesNotCatchClicks();
	} );

	QUnit.test( 'set()/empty() sense check:', function ( assert ) {
		const reuseDialog = makeReuseDialog( this.sandbox );
		const title = mw.Title.newFromText( 'File:Foobar.jpg' );
		const src = 'https://upload.wikimedia.org/wikipedia/commons/3/3a/Foobar.jpg';
		const url = 'https://commons.wikimedia.org/wiki/File:Foobar.jpg';
		const image = { // fake ImageModel
			title: title,
			url: src,
			descriptionUrl: url,
			width: 100,
			height: 80
		};
		const embedFileInfo = {
			imageInfo: title,
			repoInfo: src,
			caption: url
		};

		reuseDialog.set( image, embedFileInfo );
		reuseDialog.empty();

		assert.true( true, 'Set/empty did not cause an error.' );
	} );

	QUnit.test( 'openDialog()/closeDialog():', function ( assert ) {
		const reuseDialog = makeReuseDialog( this.sandbox );
		const title = mw.Title.newFromText( 'File:Foobar.jpg' );
		const src = 'https://upload.wikimedia.org/wikipedia/commons/3/3a/Foobar.jpg';
		const url = 'https://commons.wikimedia.org/wiki/File:Foobar.jpg';
		const image = { // fake ImageModel
			title: title,
			url: src,
			descriptionUrl: url,
			width: 100,
			height: 80
		};
		const repoInfo = new Repo( 'Wikipedia', '//wikipedia.org/favicon.ico', true );

		reuseDialog.initTabs();

		reuseDialog.set( image, repoInfo );

		assert.strictEqual( reuseDialog.isOpen, false, 'Dialog closed by default.' );

		reuseDialog.openDialog();

		assert.strictEqual( reuseDialog.isOpen, true, 'Dialog open now.' );

		reuseDialog.closeDialog();

		assert.strictEqual( reuseDialog.isOpen, false, 'Dialog closed now.' );
	} );

	QUnit.test( 'getImageWarnings():', function ( assert ) {
		const reuseDialog = makeReuseDialog( this.sandbox );
		const title = mw.Title.newFromText( 'File:Foobar.jpg' );
		const src = 'https://upload.wikimedia.org/wikipedia/commons/3/3a/Foobar.jpg';
		const url = 'https://commons.wikimedia.org/wiki/File:Foobar.jpg';
		const image = { // fake ImageModel
			title: title,
			url: src,
			descriptionUrl: url,
			width: 100,
			height: 80
		};
		const imageDeleted = Object.assign( { deletionReason: 'deleted file test' }, image );

		// Test that the lack of license is picked up
		assert.strictEqual( reuseDialog.getImageWarnings( image ).length, 1, 'Lack of license detected' );

		// Test that deletion supersedes other warnings and only that one is reported
		assert.strictEqual( reuseDialog.getImageWarnings( imageDeleted ).length, 1, 'Deletion detected' );
	} );

}() );

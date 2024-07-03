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

const DownloadPane = require( 'mmv.ui.download.pane' );

( function () {
	QUnit.module( 'mmv.ui.download.pane', QUnit.newMwEnvironment() );

	QUnit.test( 'Sense test, object creation and UI construction', function ( assert ) {
		const download = new DownloadPane( $( '#qunit-fixture' ) );

		assert.true( download instanceof DownloadPane, 'download UI element is created.' );
		assert.strictEqual( download.$pane.length, 1, 'Pane div created.' );
		assert.strictEqual( download.$downloadButton.length, 1, 'Download button created.' );
		assert.strictEqual( download.$selectionArrow.length, 1, 'Download button created.' );
		assert.true( download.downloadSizeMenu instanceof OO.ui.DropdownWidget, 'Image size pulldown menu created.' );
		assert.strictEqual( download.$previewLink.length, 1, 'Preview link created.' );
		assert.true( download.defaultItem instanceof OO.ui.MenuOptionWidget, 'Default item set.' );

		assert.strictEqual( download.$downloadButton.html(), '', 'Button has empty content.' );
		assert.strictEqual( download.$downloadButton.attr( 'href' ), undefined, 'Button href is empty.' );
		assert.strictEqual( download.$previewLink.attr( 'href' ), undefined, 'Preview link href is empty.' );
	} );

	QUnit.test( 'set()/empty():', function ( assert ) {
		const download = new DownloadPane( $( '#qunit-fixture' ) );
		const src = 'https://upload.wikimedia.org/wikipedia/commons/3/3a/Foobar.jpg';
		const image = { // fake ImageModel
			title: new mw.Title( 'File:Foobar.jpg' ),
			url: src
		};

		assert.strictEqual( download.imageExtension, undefined, 'Image extension is not set.' );

		download.utils.updateMenuOptions = function () {
			assert.true( true, 'Menu options updated.' );
		};
		download.downloadSizeMenu.getMenu().selectItem = function () {
			assert.true( true, 'Default item selected to update the labels.' );
		};

		download.set( image );

		assert.strictEqual( download.imageExtension, 'jpg', 'Image extension is set correctly.' );

		download.empty();

		assert.strictEqual( download.imageExtension, undefined, 'Image extension is not set.' );
	} );

	QUnit.test( 'attach()/unattach():', function ( assert ) {
		const download = new DownloadPane( $( '#qunit-fixture' ) );
		const image = {
			title: new mw.Title( 'File:Foobar.jpg' ),
			url: 'https://upload.wikimedia.org/wikipedia/commons/3/3a/Foobar.jpg'
		};

		download.set( image );

		const hsstub = this.sandbox.stub( download, 'handleSizeSwitch' );
		const tstub = this.sandbox.stub( download.downloadSizeMenu.getMenu(), 'toggle' );

		// Triggering action events before attaching should do nothing
		download.downloadSizeMenu.getMenu().emit(
			'choose', download.downloadSizeMenu.getMenu().findSelectedItem() );
		download.$selectionArrow.trigger( 'click' );

		assert.strictEqual( hsstub.called, false, 'handleSizeSwitch not called' );
		assert.strictEqual( tstub.called, false, 'Menu selection did not happen' );

		hsstub.reset();
		tstub.reset();

		download.attach();

		// Action events should be handled now
		download.downloadSizeMenu.getMenu().emit(
			'choose', download.downloadSizeMenu.getMenu().findSelectedItem() );
		download.$selectionArrow.trigger( 'click' );

		assert.strictEqual( hsstub.called, true, 'handleSizeSwitch was called' );
		assert.strictEqual( tstub.called, true, 'Menu selection happened' );

		hsstub.reset();
		tstub.reset();

		download.unattach();

		// Triggering action events now that we are unattached should do nothing
		download.downloadSizeMenu.getMenu().emit(
			'choose', download.downloadSizeMenu.getMenu().findSelectedItem() );
		download.$selectionArrow.trigger( 'click' );

		assert.strictEqual( hsstub.called, false, 'handleSizeSwitch not called' );
		assert.strictEqual( tstub.called, false, 'Menu selection did not happen' );
	} );

	QUnit.test( 'handleSizeSwitch():', function ( assert ) {
		const download = new DownloadPane( $( '#qunit-fixture' ) );
		const newImageUrl = 'https://upload.wikimedia.org/wikipedia/commons/3/3a/NewFoobar.jpg';

		download.utils.getThumbnailUrlPromise = function () {
			return $.Deferred().resolve( { url: newImageUrl } ).promise();
		};

		download.setDownloadUrl = function ( url ) {
			assert.strictEqual( url, newImageUrl, 'URL passed to setDownloadUrl is correct' );
		};

		download.handleSizeSwitch( download.downloadSizeMenu.getMenu().findSelectedItem() );

		assert.true( /original.*/.test( download.$downloadButton.html() ), 'Button message updated.' );

		download.image = { url: newImageUrl };

		download.utils.getThumbnailUrlPromise = function () {
			assert.true( false, 'Should not fetch the thumbnail if the image is original size.' );
		};

		download.handleSizeSwitch( download.downloadSizeMenu.getMenu().findSelectedItem() );
	} );

	QUnit.test( 'setButtonText() sense check:', function ( assert ) {
		const download = new DownloadPane( $( '#qunit-fixture' ) );

		download.setButtonText( 'large', 'jpg', 100, 200 );
		assert.true( true, 'Setting the text did not cause any errors' );

		const message = download.$downloadButton.html();
		download.setButtonText( 'small', 'png', 1000, 2000 );
		assert.notStrictEqual( download.$downloadButton.html(), message, 'Button text was updated' );
	} );

	QUnit.test( 'getExtensionFromUrl():', function ( assert ) {
		const download = new DownloadPane( $( '#qunit-fixture' ) );

		assert.strictEqual( download.getExtensionFromUrl( 'http://example.com/bing/foo.bar.png' ),
			'png', 'Extension is parsed correctly' );
	} );

	QUnit.test( 'setDownloadUrl', function ( assert ) {
		const download = new DownloadPane( $( '#qunit-fixture' ) );
		const imageUrl = 'https://upload.wikimedia.org/wikipedia/commons/3/3a/NewFoobar.jpg';

		download.setDownloadUrl( imageUrl );

		assert.strictEqual( download.$downloadButton.attr( 'href' ), imageUrl + '?download', 'Download link is set correctly.' );
		assert.strictEqual( download.$previewLink.attr( 'href' ), imageUrl, 'Preview link is set correctly.' );
		assert.strictEqual( download.$downloadButton.hasClass( 'disabledLink' ), false, 'Download link is enabled.' );
	} );
}() );

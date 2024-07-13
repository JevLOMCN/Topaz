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

const { Share } = require( 'mmv.ui.reuse.shareembed' );

( function () {
	function makeShare() {
		return new Share( $( '#qunit-fixture' ) );
	}

	QUnit.module( 'mmv.ui.reuse.share', QUnit.newMwEnvironment() );

	QUnit.test( 'Sense test, object creation and UI construction', function ( assert ) {
		const share = makeShare();

		assert.true( share instanceof Share, 'Share UI element is created.' );
		assert.strictEqual( share.$pane.length, 1, 'Pane div created.' );
		assert.true( share.pageInput instanceof mw.widgets.CopyTextLayout, 'Text field created.' );
		assert.strictEqual( share.$pageLink.length, 1, 'Link created.' );
	} );

	QUnit.test( 'set()/empty():', function ( assert ) {
		const share = makeShare();
		const image = { // fake ImageModel
			title: new mw.Title( 'File:Foobar.jpg' ),
			url: 'https://upload.wikimedia.org/wikipedia/commons/3/3a/Foobar.jpg',
			descriptionUrl: '//commons.wikimedia.org/wiki/File:Foobar.jpg'
		};

		assert.notStrictEqual( !share.pageInput.textInput.getValue(), '', 'pageInput is empty.' );

		share.select = function () {
			assert.true( true, 'Text has been selected after data is set.' );
		};

		share.set( image );

		assert.notStrictEqual( share.pageInput.textInput.getValue(), '', 'pageInput is not empty.' );

		share.empty();

		assert.notStrictEqual( !share.pageInput.textInput.getValue(), '', 'pageInput is empty.' );
	} );

}() );
